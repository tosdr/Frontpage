<?php

/*
 * Copyright (C) 2021 Justin René Back <justin@tosdr.org>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace crisp\api;

use crisp\core\Postgres;
use PDO;

/**
 * Some useful phoenix functions
 */
class Phoenix {

    private static ?PDO $Postgres_Database_Connection = null;

    private static function initPGDB(): void
    {
        $PostgresDB = new Postgres();
        self::$Postgres_Database_Connection = $PostgresDB->getDBConnector();
    }

    /**
     * Generates tosdr.org api data from a service id
     * @param string $ID The service ID from Phoenix to generate the API Files from
     * @return array The API data
     */
    public static function generateApiFiles(string $ID, int $Version = 1): array
    {

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }
        $SkeletonData = null;

        switch ($Version) {
            case 1:
            case 2:
                $ServiceLinks = [];
                $ServicePoints = [];
                $ServicePointsData = [];

                $points = self::getPointsByServiceScored($ID);
                $service = self::getService($ID);
                $documents = self::getDocumentsByService($ID);
                foreach ($documents as $Links) {
                    $ServiceLinks[$Links['name']] = [
                        'name' => $Links['name'],
                        'url' => $Links['url']
                    ];
                }
                foreach ($points as $Point) {
                    if ($Point['status'] === 'approved') {
                        $ServicePoints[] = $Point['id'];
                    }
                }
                foreach ($points as $Point) {
                    $Document = array_column($documents, null, 'id')[$Point['document_id']];
                    $Case = self::getCase($Point['case_id']);
                    if ($Point['status'] === 'approved') {
                        $ServicePointsData[$Point['id']] = [
                            'discussion' => 'https://edit.tosdr.org/points/' . $Point['id'],
                            'id' => $Point['id'],
                            'needsModeration' => ($Point['status'] !== 'approved'),
                            'quoteDoc' => $Document['name'],
                            'quoteText' => $Point['quoteText'],
                            'services' => [$ID],
                            'set' => 'set+service+and+topic',
                            'slug' => $Point['slug'],
                            'title' => $Point['title'],
                            'topics' => [],
                            'tosdr' => [
                                'binding' => true,
                                'case' => $Case['title'],
                                'point' => $Case['classification'],
                                'score' => $Case['score'],
                                'tldr' => $Point['analysis']
                            ],
                        ];
                    }
                }

                $SkeletonData = [
                    'id' => $service['_source']['id'],
                    'name' => $service['_source']['name'],
                    'slug' => $service['_source']['slug'],
                    'image' => Config::get('s3_logos') . '/' . ($service['_source']['image']),
                    'class' => ($service['_source']['rating'] === 'N/A' ? false : ($service['_source']['is_comprehensively_reviewed'] ? $service['_source']['rating'] : false)),
                    'links' => $ServiceLinks,
                    'points' => $ServicePoints,
                    'pointsData' => $ServicePointsData,
                    'urls' => explode(',', $service['_source']['url'])
                ];
                break;
            case 3:
                $ServicePointsData = [];

                $points = self::getPointsByService($ID);
                $service = self::getService($ID);
                $documents = self::getDocumentsByService($ID);
                foreach ($points as $Point) {
                    $Document = array_column($documents, null, 'id')[$Point['document_id']];
                    $Case = self::getCase($Point['case_id']);
                    $ServicePointsData[] = [
                        'discussion' => 'https://edit.tosdr.org/points/' . $Point['id'],
                        'id' => $Point['id'],
                        'needsModeration' => ($Point['status'] !== 'approved'),
                        'document' => $Document,
                        'quote' => $Point['quoteText'],
                        'services' => [$ID],
                        'set' => 'set+service+and+topic',
                        'slug' => $Point['slug'],
                        'title' => $Point['title'],
                        'topics' => [],
                        'case' => $Case
                    ];
                }

                $SkeletonData = $service['_source'];

                $SkeletonData['image'] = Config::get('s3_logos') . '/' . $service['_source']['image'];
                $SkeletonData['documents'] = $documents;
                $SkeletonData['points'] = $ServicePointsData;
                $SkeletonData['urls'] = explode(',', $service['_source']['url']);
                break;
        }

        return $SkeletonData;
    }

    /**
     * Retrieve points by a service from postgres
     * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L89-L111 Database Schema
     * @param string $ID The ID of the Service
     * @return array
     */
    public static function getPointsByService(string $ID): array
    {

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }



        $statement = self::$Postgres_Database_Connection->prepare('SELECT * FROM points WHERE service_id = :ID');

        $statement->execute([':ID' => $ID]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve points by a service from postgres
     * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L89-L111 Database Schema
     * @param string $ID The ID of the Service
     * @return array
     */
    public static function getPointsByServiceScored(string $ID): array
    {

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }



        $statement = self::$Postgres_Database_Connection->prepare('SELECT * FROM points INNER JOIN cases ON points.case_id = cases.id WHERE service_id = :ID ORDER BY cases.score DESC;');

        $statement->execute([':ID' => $ID]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all documents by a service from postgres
     * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L64-L77 Database Schema
     * @param string $ID The Service ID
     * @return array
     */
    public static function getDocumentsByService(string $ID): array
    {

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare('SELECT * FROM documents WHERE service_id = :ID');

        $statement->execute([':ID' => $ID]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * List all points from postgres
     * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L89-L111 Database Schema
     * @return array
     */
    public static function getPoints(): array
    {

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        return self::$Postgres_Database_Connection->query('SELECT * FROM points')->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets details about a point from postgres
     * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L89-L111 Database Schema
     * @param string $ID The ID of a point
     * @return array
     */
    public static function getPoint(string $ID): array
    {
        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare('SELECT * FROM points WHERE id = :ID');

        $statement->execute([':ID' => $ID]);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Gets details about a case from postgres
     * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L42-L52 Database Schema
     * @param string $ID The id of a case
     * @return array
     */
    public static function getCase(string $ID): array
    {


        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare('SELECT * FROM cases WHERE id = :ID');

        $statement->execute([':ID' => $ID]);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Gets details about a topic from postgres
     * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L170-L177 Database Schema
     * @param string $ID The topic id
     * @return array
     */
    public static function getTopic(string $ID): array
    {

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare('SELECT * FROM topics WHERE id = :ID');

        $statement->execute([':ID' => $ID]);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Search for a service via postgres
     * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L134-L148 Database Schema
     * @param string $Name The name of a service
     * @return array
     */
    public static function searchServiceByName(string $Name): array
    {

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare('SELECT * FROM services WHERE LOWER(name) LIKE :ID');

        $statement->execute([':ID' => "%$Name%"]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get details of a service from postgres via a slug
     * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L134-L148 Database Schema
     * @param string $Name The slug of a service
     * @return bool|array
     */
    public static function getServiceBySlug(string $Name): bool|array
    {

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare('SELECT * FROM services WHERE LOWER(slug) = LOWER(:ID)');

        $statement->execute([':ID' => $Name]);


        if ($statement->rowCount() === 0) {
            return false;
        }

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get details of a service via postgres by name
     * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L134-L148 Database Schema
     * @param string $Name the exact name of the service
     * @return bool|array
     */
    public static function getServiceByName(string $Name): bool|array
    {
        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare('SELECT * FROM services WHERE LOWER(name) = LOWER(:ID)');

        $statement->execute([':ID' => $Name]);

        if ($statement->rowCount() === 0) {
            return false;
        }

        $response = $statement->fetch(PDO::FETCH_ASSOC);

        $response['image'] = $response['id'] . '.png';
        return $response;
    }

    /**
     * Check if a service exists from postgres via slug
     * @param string $Name The slug of the service
     * @return bool
     */
    public static function serviceExistsBySlug(string $Name): bool
    {

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare('SELECT * FROM services WHERE LOWER(slug) = LOWER(:ID)');

        $statement->execute([':ID' => $Name]);

        return $statement->rowCount() > 0;
    }

    /**
     * Create a service on phoenix
     * @param string $Name
     * @param string $Url
     * @param string $Wikipedia
     * @param string $User
     * @return bool|int
     */
    public static function createService(string $Name, string $Url, string $Wikipedia, string $User): bool | int
    {
        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        if (self::serviceExistsByName($Name)) {
            return false;
        }

        $statement = self::$Postgres_Database_Connection->prepare('INSERT INTO services (name, url, wikipedia, created_at, updated_at) VALUES (:name, :url, :wikipedia, NOW(), NOW())');

        $statement->execute([':name' => $Name, ':url' => $Url, ':wikipedia' => $Wikipedia]);

        $service_id = self::$Postgres_Database_Connection->lastInsertId();

        $Result = $statement->rowCount() > 0;

        if ($Result) {
            self::createVersion('Service', $service_id, 'create', 'Created service', $User);
            return $service_id;
        }

        return false;
    }

    /**
     * Create a version on phoenix
     * @param string $Name
     * @param string $Url
     * @param string $Xpath
     * @param string $Service
     * @param string $User
     * @return bool
     */
    public static function createDocument(string $Name, string $Url, string $Xpath, string $Service, string $User): bool
    {
        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        if (!self::serviceExists($Service)) {
            return false;
        }

        $statement = self::$Postgres_Database_Connection->prepare('INSERT INTO documents (name, url, xpath, created_at, updated_at, service_id) VALUES (:name, :url, :xpath, NOW(), NOW(), :service_id)');

        $statement->execute([':name' => $Name, ':url' => $Url, ':xpath' => $Xpath, ':service_id' => $Service]);

        $Result = $statement->rowCount() > 0;

        $document_id = self::$Postgres_Database_Connection->lastInsertId();

        if ($Result) {
            self::createVersion('Document', $document_id, 'create', 'Created document', $User);
            return $document_id;
        }

        return false;
    }

    /**
     * Create a document on phoenix
     * @param string $itemType
     * @param string $itemId
     * @param string $event
     * @param string|null $objectChanges
     * @param string $whodunnit
     * @param string|null $object
     * @return bool
     */
    public static function createVersion(string $itemType, string $itemId, string $event, string $objectChanges = null, string $whodunnit, string $object = null): bool
    {
        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare('INSERT INTO versions (item_type, item_id, event, created_at, object_changes, whodunnit, object) VALUES (:item_type, :item_id, :event, NOW(), :object_changes, :whodunnit, :object)');

        $statement->execute([
            ':item_type' => $itemType,
            ':item_id' => $itemId,
            ':event' => $event,
            ':object_changes' => $objectChanges,
            ':whodunnit' => $whodunnit,
            ':object' => $object
        ]);

        return $statement->rowCount() > 0;
    }

    /**
     * Check if a service exists from postgres via name
     * @param string $Name The name of the service
     * @return bool
     */
    public static function serviceExistsByName(string $Name): bool
    {

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare('SELECT * FROM services WHERE LOWER(name) = LOWER(:ID)');

        $statement->execute([':ID' => $Name]);

        return $statement->rowCount() > 0;
    }

    /**
     * Check if a point exists from postgres via slug
     * @param string $ID The id of the point
     * @return bool
     */
    public static function pointExists(string $ID): bool
    {
        #
        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare('SELECT * FROM points WHERE id = :ID');

        $statement->execute([':ID' => $ID]);

        return $statement->rowCount() > 0;
    }

    /**
     * Check if a service exists from postgres via the ID
     * @param string $ID The ID of the service
     * @return bool
     */
    public static function serviceExists(string $ID): bool
    {

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare('SELECT * FROM services WHERE id = :ID');

        $statement->execute([':ID' => $ID]);

        return $statement->rowCount() > 0;
    }

    public static function getService(string $ID): bool|array
    {


        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        $statement = self::$Postgres_Database_Connection->prepare('SELECT * FROM services WHERE id = :ID');

        $statement->execute([':ID' => $ID]);

        if ($statement->rowCount() === 0) {
            return false;
        }

        $response = $statement->fetch(PDO::FETCH_ASSOC);


        $response['image'] = $response['id'] . '.png';
        $dummy = [];

        $dummy['_source'] = $response;
        return $dummy;
    }

    /**
     * List all topics from postgres
     * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L170-L177 Database Schema
     * @return array
     */
    public static function getTopics(): array
    {


        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        return self::$Postgres_Database_Connection->query('SELECT * FROM topics')->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * List all cases from postgres
     * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L42-L52 Database Schema
     * @return array
     */
    public static function getCases(): array
    {

        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        return self::$Postgres_Database_Connection->query('SELECT * FROM cases')->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * List all services from postgres
     * @see https://github.com/tosdr/edit.tosdr.org/blob/8b900bf8879b8ed3a4a2a6bbabbeafa7d2ab540c/db/schema.rb#L134-L148 Database Schema
     * @return array
     */
    public static function getServices(): array
    {
        if (self::$Postgres_Database_Connection === NULL) {
            self::initPGDB();
        }

        return self::$Postgres_Database_Connection->query("SELECT * FROM services WHERE status IS NULL or status = ''")->fetchAll(PDO::FETCH_ASSOC);
    }

}
