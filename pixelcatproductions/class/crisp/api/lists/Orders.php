<?php

/*
 * Copyright (C) 2020 Justin Back <jback@pixelcatproductions.net>
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

namespace crisp\api\lists;

use \PDO;
use \PDOException;
use \PDORow;
use \PDOStatement;

/**
 * Interact with all photos stored on the server
 */
class Orders {

    private static ?PDO $Database_Connection = null;

    public function __construct() {
        self::initDB();
    }

    private static function initDB() {
        $DB = new \crisp\core\MySQL();
        self::$Database_Connection = $DB->getDBConnector();
    }

    public function fetchUser($OrderID) {
        if ($OrderID === null) {
            return null;
        }

        $statement = self::$Database_Connection->prepare("SELECT * FROM Orders WHERE ID = :ID");
        $statement->execute(array(":ID" => $OrderID));

        $User = new \crisp\api\User($statement->fetch(\PDO::FETCH_ASSOC)["User"]);

        return $User;
    }

    public static function fetchAll(bool $FetchIntoClass = true) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Orders");
        $statement->execute();

        if ($FetchIntoClass) {
            $Array = array();

            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Order) {
                array_push($Array, new \crisp\api\Order($Order["ID"]));
            }
            return $Array;
        }
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function fetchOrder(int $OrderID, bool $FetchIntoClass = true) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }

        $statement = self::$Database_Connection->prepare("SELECT * FROM Orders WHERE ID = :Order;");
        $statement->execute(array(":Order" => $OrderID));

        if ($statement->rowCount() == 0) {
            return false;
        }

        if ($FetchIntoClass) {
            return new \crisp\api\Order($statement->fetch(\PDO::FETCH_ASSOC)["ID"]);
        }
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    public static function fetchOrderByProcessorID(string $ProcessorID, bool $FetchIntoClass = true) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }

        $statement = self::$Database_Connection->prepare("SELECT * FROM Orders WHERE ProcessorID = :Order;");
        $statement->execute(array(":Order" => $ProcessorID));

        if ($statement->rowCount() == 0) {
            return false;
        }

        if ($FetchIntoClass) {
            return new \crisp\api\Order($statement->fetch(\PDO::FETCH_ASSOC)["ID"]);
        }
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    public static function fetchAllByUser(int $UserID, bool $FetchIntoClass = true, $Limit = 100) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Orders WHERE User = :User ORDER BY ID DESC LIMIT $Limit;");
        $statement->execute(array(":User" => $UserID));

        if ($FetchIntoClass) {
            $Array = array();

            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Order) {
                array_push($Array, new \crisp\api\Order($Order["ID"]));
            }
            return $Array;
        }
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function create($User, $Email, $Items, $Address, $Amount, $PaymentMethod, $Metadata, $OriginalAmount = null, $Tax = null) {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $Amount = ($OriginalAmount === null ? $OriginalAmount : $Amount);
        $statement = self::$Database_Connection->prepare("INSERT INTO Orders (User, Email, Items, Address, Amount, PaymentMethod, Metadata, OriginalAmount, Tax) VALUES (:User, :Email, :Items, :Address, :Amount, :PaymentMethod, :Metadata, :OriginalAmount, :Tax)");
        $created = $statement->execute(array(":User" => $User, ":Email" => $Email, ":Items" => $Items, ":Address" => $Address, ":Amount" => $Amount, ":PaymentMethod" => $PaymentMethod, ":Metadata" => $Metadata, ":OriginalAmount" => $OriginalAmount, ":Tax" => $Tax));
        if ($created) {
            return new \crisp\api\Order(self::$Database_Connection->lastInsertId());
        }
        return false;
    }

}
