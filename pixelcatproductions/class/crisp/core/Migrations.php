<?php

/*
 * Copyright 2020 Pixelcat Productions <support@pixelcatproductions.net>
 * @author 2020 Justin René Back <jback@pixelcatproductions.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace crisp\core;

/**
 * Crisp DB Migration Class
 * @since 0.0.8-beta.RC1
 */
class Migrations {

    /**
     * The PDO Database
     * @var \PDO
     */
    protected \PDO $Database;

    /* Data types */

    const DB_VARCHAR = "varchar(255)";
    const DB_TEXT = "text";
    const DB_INTEGER = "integer";
    const DB_TIMESTAMP = "datetime";
    const DB_BOOL = "tinyint(1)";
    const DB_LONGTEXT = "LONGTEXT";

    /* Keys */
    const DB_PRIMARYKEY = "PRIMARY";
    const DB_UNIQUEKEY = "UNIQUE";

    public function __construct() {
        $DB = new MySQL();
        $this->Database = $DB->getDBConnector();
    }

    /**
     * Begin a MySQL Transaction
     * @since 0.0.8-beta.RC2
     * @return boolean
     */
    protected function begin() {
        echo "Enabling Transactions..." . PHP_EOL;
        if ($this->Database->beginTransaction()) {
            echo "Enabled Transactions!" . PHP_EOL;
            return true;
        }
        echo "Failed to enable transactions..." . PHP_EOL;
        return false;
    }

    /**
     * Rollback a MySQL Transaction
     * @since 0.0.8-beta.RC2
     * @return boolean
     */
    protected function rollback() {
        echo "Rolling back..." . PHP_EOL;
        if ($this->Database->rollBack()) {
            echo "Rolled back!" . PHP_EOL;
            return true;
        }
        echo "Failed to rollback..." . PHP_EOL;
        return false;
    }

    /**
     * End/commit a MySQL Transaction
     * @since 0.0.8-beta.RC2
     * @return boolean
     */
    protected function end() {
        echo "committing changes..." . PHP_EOL;
        if ($this->Database->commit()) {
            echo "Changes committed!" . PHP_EOL;
            return true;
        }
        echo "Failed to commit changes!" . PHP_EOL;
        return false;
    }

    /**
     * Check if a migration is already installed
     * @param string $file The migration filename to check. Don't use the extension in the filename.
     * @see basename
     * @since 0.0.8-beta.RC2
     * @return boolean
     */
    public function isMigrated(string $file) {

        try {
            $statement = $this->Database->prepare("SELECT * FROM schema_migration WHERE file =:file");

            $statement->execute(array(":file" => $file));
            return ($statement->rowCount() > 0 ? true : false);
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * Begin the migration of the database
     * @since 0.0.8-beta.RC2
     * @return void
     */
    public function migrate() {
        echo "Starting Migration..." . PHP_EOL;
        $files = glob(__DIR__ . '/../migrations/*.{php}', GLOB_BRACE);
        foreach ($files as $file) {
            if (basename($file) == "template.php") {
                continue;
            }

            $MigrationName = substr(basename($file), 0, -4);

            if ($this->isMigrated($MigrationName)) {
                echo "$MigrationName is already migrated, skipping!" . PHP_EOL;
                continue;
            }

            $Class = "\crisp\migrations\\" . explode("_", $MigrationName)[1];

            include $file;

            $Migration = new $Class();

            if ($Migration->run()) {

                $statement = $this->Database->prepare("INSERT INTO schema_migration (file) VALUES (:file)");

                $statement->execute(array(":file" => $MigrationName));
                echo "Migrated $MigrationName" . PHP_EOL;
            } else {
                echo "Failed to migrate $MigrationName" . PHP_EOL;
            }
        }
    }

    /**
     * Create a new migration file.
     * @param string $MigrationName The name of the migration, only Alpha Letters.
     * @since 0.0.8-beta.RC2
     * @return void
     */
    public function create(string $MigrationName) {

        $MigrationNameFiltered = \crisp\api\Helper::filterAlphaNum($MigrationName);

        $Template = file_get_contents(__DIR__ . "/../migrations/template.php");

        $Skeleton = strtr($Template, array(
            "MigrationName" => $MigrationNameFiltered,
            "RUNCODE;" => '$this->createTable("MyTable", array("col1", \crisp\core\Migrations::DB_VARCHAR));'
        ));

        $written = file_put_contents(__DIR__ . "/../migrations/" . time() . "_$MigrationNameFiltered.php", $Skeleton);

        if (!$written) {
            echo "Failed to write migration file, check permissions!" . PHP_EOL;
        } else {
            echo "Migration file written!" . PHP_EOL;
        }
    }

    /**
     * Create a new index for a table
     * @param string $Table The name of the table
     * @param array $Column An array consisting of the column name, column type and additional info, e.g. array("ColName, self::DB_VARCHAR, "NOT NULL")
     * @param const $Type The type of the index
     * @param string $IndexName The name of the index, Unused if PRIMARYKEY
     * @return boolean
     * @since 0.0.8-beta.RC2
     * @throws \Exception on PDO Error
     */
    protected function addIndex(string $Table, array $Column, $Type = self::DB_PRIMARYKEY, string $IndexName = null) {
        $SQL = "";
        echo "Adding index to table $Table..." . PHP_EOL;
        if ($Type == self::DB_PRIMARYKEY) {
            $SQL = "ALTER TABLE `$Table` ADD $Type KEY (`$Column`);";
        } else {
            $SQL = "ALTER TABLE `$Table` ADD $Type INDEX `$IndexName` (`$Column`);";
        }

        $statement = $this->Database->prepare($SQL);

        if ($statement->execute()) {
            echo "Added Index to Table $Table!" . PHP_EOL;
            return true;
        }
        echo "Failed to add Index to Table $Table!" . PHP_EOL;
        throw new \Exception($statement->errorInfo());
    }

    /**
     * Add a column to a table
     * @param string $Table The table name
     * @param array $Column An array consisting of the column name, column type and additional info, e.g. array("ColName, self::DB_VARCHAR, "NOT NULL")
     * @return boolean
     * @throws \Exception on PDO Error
     * @since 0.0.8-beta.RC2
     */
    protected function addColumn(string $Table, array $Column) {
        echo "Adding column to Table $Table..." . PHP_EOL;
        $SQL = "ALTER TABLE `$Table` ADD COLUMN `$Column[0]` $Column[1] $Column[2];";

        $statement = $this->Database->prepare($SQL);

        if ($statement->execute()) {
            echo "Added Column to Table $Table!" . PHP_EOL;
            return true;
        }
        echo "Failed to add Column to Table $Table!" . PHP_EOL;
        throw new \Exception($statement->errorInfo());
    }

    /**
     * Create a new table. This function accepts infinite parameters to add columns
     * @param string $Table The table name
     * @param mixed ...$Columns An array consisting of the column name, column type and additional info, e.g. array("ColName, self::DB_VARCHAR, "NOT NULL")
     * @return boolean
     * @since 0.0.8-beta.RC2
     * @throws \Exception
     */
    protected function createTable(string $Table, ...$Columns) {
        echo "Creating Table $Table..." . PHP_EOL;
        $SQL = "CREATE TABLE `$Table` (";
        $AutoIncrement = false;
        foreach ($Columns as $Key => $Column) {
            $Name = $Column[0];
            $Type = $Column[1];
            $Additional = $Column[2];
            if (strpos($Additional, "AUTO_INCREMENT") !== false) {
                $AutoIncrement = $Name;
            }
            $SQL .= "`$Name` $Type $Additional,";
            if ($Key == count($Columns) - 1) {
                $SQL = substr($SQL, 0, -1);
                if ($AutoIncrement !== false) {
                    $SQL .= ", KEY `$AutoIncrement` (`$AutoIncrement`)";
                }
            }
        }
        $SQL .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";


        $statement = $this->Database->prepare($SQL);

        if ($statement->execute()) {
            echo "Creating Table $Table!" . PHP_EOL;
            return true;
        }
        echo "Failed to create Table $Table!" . PHP_EOL;
        throw new \Exception($statement->errorInfo());
    }

}
