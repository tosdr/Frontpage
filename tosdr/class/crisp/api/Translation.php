<?php

/* 
 * Copyright (C) 2021 Justin René Back <justin@tosdr.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


namespace crisp\api;

use crisp\api\lists\Languages;
use crisp\core\MySQL;
use PDO;

/**
 * Access the translations of the CMS
 */
class Translation
{

    /**
     * The Language code
     * @var string|null
     */
    public static ?string $Language = null;
    private static ?PDO $Database_Connection = null;

    /**
     * Sets the language code and inits the database connection for further use of functions in this class
     * @param string|null $Language The Language code or null
     */
    public function __construct(?string $Language)
    {
        self::$Language = $Language;
    }

    /**
     * Inits DB
     */
    private static function initDB(): void
    {
        $DB = new MySQL();
        self::$Database_Connection = $DB->getDBConnector();
    }

    /**
     * Same as \crisp\api\lists\Languages()->fetchLanguages()
     * @param bool $FetchIntoClass Should the result be fetched into a \crisp\api\Language class
     * @return Language|array depending on the $FetchIntoClass parameter
     * @uses  \crisp\api\lists\Languages()
     */
    public static function listLanguages(bool $FetchIntoClass = true): array|Language
    {
        return Languages::fetchLanguages($FetchIntoClass);
    }

    /**
     * Retrieves all translations with key and language code
     * @return array containing all translations on the server
     */
    public static function listTranslations(): array
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->query('SELECT * FROM Translations');
        if ($statement->rowCount() > 0) {
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /**
     * Retrieves all translations for the specified self::$Language
     * @return array containing all translations for the self::$Language
     * @uses self::$Language
     */
    public static function fetchAll(): array
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->query('SELECT * FROM Translations');
        if ($statement->rowCount() > 0) {

            $Translations = $statement->fetchAll(PDO::FETCH_ASSOC);

            $Array = [];

            foreach (lists\Languages::fetchLanguages() as $Language) {

                $lCode = $Language->getCode();

                $Array[$lCode] = [];
                foreach ($Translations as $Item) {
                    $Array[$lCode][$Item['key']] = $Item[$lCode];
                }
            }

            return $Array;
        }
        return [];
    }

    /**
     * Fetch all translations by key
     * @param string $Key The letter code
     * @return array
     */
    public static function fetchAllByKey(string $Key): array
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->query('SELECT * FROM Translations');
        if ($statement->rowCount() > 0) {

            $Translations = $statement->fetchAll(PDO::FETCH_ASSOC);

            $Array = [];
            foreach ($Translations as $Item) {
                if (str_contains($Item['key'], 'plugin.')) {
                    continue;
                }
                if ($Item[$Key] === null) {
                    continue;
                }
                $Array[$Key][$Item['key']] = $Item[$Key];
            }

            return $Array[$Key];
        }
        return [];
    }

    /**
     * Check if a translation exists by key
     * @param string $Key The translation key
     * @return bool
     */
    public static function exists(string $Key): bool
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare('SELECT * FROM Translations WHERE key = :key');
        $statement->execute([':key' => $Key]);
        return ($statement->rowCount() > 0);
    }

    /**
     * Fetches translations for the specified key
     * @param string $Key The translation key
     * @param int $Count Used for the plural and singular retrieval of translations, also exposes {{ count }} in templates.
     * @param array $UserOptions Custom array used for templating
     * @return string The translation or the key if it doesn't exist
     */
    public static function fetch(string $Key, int $Count = 1, array $UserOptions = []): string
    {

        if (!isset(self::$Language)) {
            self::$Language = Helper::getLocale();
        }


        if (isset($GLOBALS['route']->GET['debug']) && $GLOBALS['route']->GET['debug'] = 'translations') {
            return "$Key:" . self::$Language;
        }

        $UserOptions['{{ count }}'] = $Count;

        return nl2br(ngettext(self::get($Key, $UserOptions), self::getPlural($Key, $UserOptions), $Count));
    }

    /**
     * Fetches all singular translations for the specified key
     * @param string $Key The translation key
     * @param array $UserOptions Custom array used for templating
     * @return string The translation or the key if it doesn't exist
     * @see getPlural
     * @see fetch
     */
    public static function get(string $Key, array $UserOptions = []): string
    {

        if (self::$Database_Connection === null) {
            self::initDB();
        }

        $GlobalOptions = [];
        foreach (Config::list(true) as $Item) {
            $GlobalOptions["{{ config.{$Item['key']} }}"] = $Item['value'];
        }

        $Options = array_merge($UserOptions, $GlobalOptions);


        $statement = self::$Database_Connection->prepare('SELECT * FROM Translations WHERE key = :Key');
        $statement->execute([
            ':Key' => $Key,
            //":Language" => $this->Language
        ]);
        if ($statement->rowCount() > 0) {

            $Translation = $statement->fetch(PDO::FETCH_ASSOC);

            if (!isset($Translation[strtolower(self::$Language)])) {
                if (self::$Language === 'en') {
                    return $Key;
                }
                return $Translation['en'];
            }

            return strtr($Translation[strtolower(self::$Language)], $Options);
        }
        return $Key;
    }

    /**
     * Fetches all plural translations for the specified key
     * @param string $Key The translation key
     * @param array $UserOptions Custom array used for templating
     * @return string The translation or the key if it doesn't exist
     * @see get
     * @see fetch
     */
    public static function getPlural(string $Key, array $UserOptions = []): string
    {

        if (self::$Database_Connection === null) {
            self::initDB();
        }

        $GlobalOptions = [];

        foreach (Config::list(true) as $Item) {
            $GlobalOptions["{{ config.{$Item['key']} }}"] = $Item['value'];
        }

        $Options = array_merge($UserOptions, $GlobalOptions);


        $statement = self::$Database_Connection->prepare('SELECT * FROM Translations WHERE key = :Key');
        $statement->execute([
            ':Key' => $Key . '.plural',
            //":Language" => $this->Language
        ]);
        if ($statement->rowCount() > 0) {
            $Translation = $statement->fetch(PDO::FETCH_ASSOC);

            if ($Translation[strtolower(self::$Language)] === null) {
                if (self::$Language === 'en') {
                    return $Key . '.plural';
                }
                return strtr($Translation['en'], $Options);
            }

            return strtr($Translation[strtolower(self::$Language)], $Options);
        }
        return $Key . '.plural';
    }

}
