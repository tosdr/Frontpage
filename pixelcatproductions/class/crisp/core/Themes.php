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
 * Used internally, theme loader
 *
 */
class Themes {

  use \crisp\core\Hook;

  /**
   * Clear the theme cache
   * @return boolean
   */
  public static function clearCache() {
    $it = new \RecursiveDirectoryIterator(realpath(__DIR__ . "/../../../cache/"), \RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new \RecursiveIteratorIterator($it,
            \RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
      if ($file->isDir()) {
        rmdir($file->getRealPath());
      } else {
        unlink($file->getRealPath());
      }
    }
    return true;
  }

  public static function getThemeMode() {
    if (isset($_COOKIE[\crisp\core\Config::$Cookie_Prefix . "theme_mode"])) {
      $Mode = $_COOKIE[\crisp\core\Config::$Cookie_Prefix . "theme_mode"];
    } else {
      $Mode = "0";
    }
    return $Mode;
  }

  public static function setThemeMode(string $Mode) {
    return setcookie(\crisp\core\Config::$Cookie_Prefix . "theme_mode", $Mode, time() + (86400 * 30), "/");
  }

  public static function load($TwigTheme, $CurrentFile, $CurrentPage) {
    try {
      if (count($GLOBALS["render"]) === 0) {
        if (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/includes/$CurrentPage.php") && \crisp\api\Helper::templateExists(\crisp\api\Config::get("theme"), "/views/$CurrentPage.twig")) {
          new \crisp\core\Theme($TwigTheme, $CurrentFile, $CurrentPage);
        } else {
          $GLOBALS["microtime"]["logic"]["end"] = microtime(true);
          $GLOBALS["microtime"]["template"]["start"] = microtime(true);
          $TwigTheme->addGlobal("LogicMicroTime", ($GLOBALS["microtime"]["logic"]["end"] - $GLOBALS["microtime"]["logic"]["start"]));
          http_response_code(404);
          echo $TwigTheme->render("errors/notfound.twig", []);
        }
      }
    } catch (\Exception $ex) {
      throw new \Exception($ex);
    }
  }

  public static function includeResource($File, bool $Prefix = true, string $CDN = "cdn") {
    if (strpos($File, "/") === 0) {
      $File = substr($File, 1);
    }

    if (!file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/$File")) {
      return (\crisp\api\Config::exists($CDN) ? \crisp\api\Config::get($CDN) : "") . ($Prefix ? "/" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") : "") . "/$File";
    }

    return (\crisp\api\Config::exists($CDN) ? \crisp\api\Config::get($CDN) : "") . "/" . ($Prefix ? \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") : "") . "/$File?" . hash_file("sha256", __DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/$File");
  }

  private static function performOnInstall($ThemeName, $ThemeMetadata) {
    if (!isset($ThemeMetadata->onInstall)) {
      return false;
    }

    self::installKVStorage($ThemeMetadata);
    self::installTranslations($ThemeName, $ThemeMetadata);

    if (isset($ThemeMetadata->onInstall->activateDependencies) && \is_array($ThemeMetadata->onInstall->activateDependencies)) {
      foreach ($ThemeMetadata->onInstall->activateDependencies as $Theme) {
        if (!self::isInstalled($Theme)) {
          self::install($Theme);
        }
      }
    }
  }

  public static function refreshTranslations($ThemeName, $ThemeMetadata) {
    self::uninstallTranslations($ThemeMetadata);
    return self::installTranslations($ThemeName, $ThemeMetadata);
  }

  public static function refreshKVStorage($ThemeMetadata) {
    self::uninstallKVStorage($ThemeMetadata);
    return self::installKVStorage($ThemeMetadata);
  }

  public static function installKVStorage($ThemeMetadata) {
    if (!\is_object($ThemeMetadata) && !isset($ThemeMetadata->hookFile)) {
      return false;
    }
    if (isset($ThemeMetadata->onInstall->createKVStorageItems) && \is_object($ThemeMetadata->onInstall->createKVStorageItems)) {
      foreach ($ThemeMetadata->onInstall->createKVStorageItems as $Key => $Value) {
        if (is_array($Value) || \is_object($Value)) {
          $Value = \serialize($Value);
        }
        try {
          \crisp\api\Config::create($Key, $Value);
        } catch (\PDOException $ex) {
          continue;
        }
      }
    }
    return true;
  }

  public static function installTranslations($ThemeName, $ThemeMetadata) {
    if (!\is_object($ThemeMetadata) && !isset($ThemeMetadata->hookFile)) {
      return false;
    }

    $_processed = [];
    if (defined("CRISP_CLI")) {
      echo "----------" . PHP_EOL;
      echo "Installing translations for theme $ThemeName" . PHP_EOL;
      echo "----------" . PHP_EOL;
    }

    if (isset($ThemeMetadata->onInstall->createTranslationKeys) && is_string($ThemeMetadata->onInstall->createTranslationKeys)) {

      $ThemeFolder = \crisp\api\Config::get("theme_dir");
      if (file_exists(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/" . $ThemeMetadata->onInstall->createTranslationKeys)) {

        $files = glob(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/" . $ThemeMetadata->onInstall->createTranslationKeys . "*.{json}", GLOB_BRACE);
        foreach ($files as $File) {

          if (defined("CRISP_CLI")) {
            echo "----------" . PHP_EOL;
            echo "Installing language " . substr(basename($File), 0, -5) . PHP_EOL;
            echo "----------" . PHP_EOL;
          }
          if (!file_exists($File)) {
            continue;
          }
          $Language = \crisp\api\lists\Languages::getLanguageByCode(substr(basename($File), 0, -5));

          if (!$Language) {
            continue;
          }

          foreach (json_decode(file_get_contents($File), true) as $Key => $Value) {
            try {

              if ($Language->newTranslation($Key, $Value, substr(basename($File), 0, -5))) {
                $_processed[] = $Key;
                if (defined("CRISP_CLI")) {
                  echo "Installing key $Key" . PHP_EOL;
                }
              }
            } catch (\PDOException $ex) {
              continue 2;
            }
          }

          if (defined("CRISP_CLI")) {
            echo "Installed/Updated " . count($_processed) . " keys" . PHP_EOL;
            $_processed = [];
          }
        }
      }
      return true;
    }
    if (isset($ThemeMetadata->onInstall->createTranslationKeys) && \is_object($ThemeMetadata->onInstall->createTranslationKeys)) {
      foreach ($ThemeMetadata->onInstall->createTranslationKeys as $Key => $Value) {

        try {
          $Language = \crisp\api\lists\Languages::getLanguageByCode($Key);

          if (!$Language) {
            continue;
          }

          foreach ($Value as $KeyTranslation => $ValueTranslation) {
            $Language->newTranslation($KeyTranslation, $ValueTranslation, $Key);
          }
        } catch (\PDOException $ex) {
          continue;
        }
      }
      return true;
    }
    return false;
  }

  /**
   * Checks if the specified theme is installed
   * @param string $ThemeName The folder name of the theme
   * @return boolean TRUE if theme is installed, otherwise FALSE
   */
  public static function isInstalled($ThemeName) {
    return (\crisp\api\Config::get("theme") == $ThemeName);
  }

  public static function isValid($ThemeName) {
    $ThemeFolder = \crisp\api\Config::get("theme_dir");
    return file_exists(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/theme.json");
  }

  public static function uninstall($ThemeName) {

    $ThemeFolder = \crisp\api\Config::get("theme_dir");

    if (!\file_exists(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/theme.json")) {
      return false;
    }

    self::clearCache();

    \crisp\api\Config::set("theme", null);

    $ThemeMetadata = json_decode(\file_get_contents(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/theme.json"));


    if (!\is_object($ThemeMetadata) && !isset($ThemeMetadata->hookFile)) {
      return false;
    }
    self::performOnUninstall($ThemeName, $ThemeMetadata);



    self::broadcastHook("themeUninstall_$ThemeName", null);
    self::broadcastHook("themeUninstall", $ThemeName);
    return true;
  }

  private static function performOnUninstall($ThemeName, $ThemeMetadata) {

    if (isset($ThemeMetadata->onUninstall->purgeDependencies) && \is_array($ThemeMetadata->onUninstall->purgeDependencies)) {
      foreach ($ThemeMetadata->onUninstall->purgeDependencies as $Theme) {
        self::deleteData($Theme);
      }
    } else if (isset($ThemeMetadata->onUninstall->deactivateDependencies) && \is_array($ThemeMetadata->onUninstall->deactivateDependencies)) {
      foreach ($ThemeMetadata->onUninstall->deactivateDependencies as $Theme) {
        self::uninstall($Theme);
      }
    }
    if ($ThemeMetadata->onUninstall->deleteData) {
      self::deleteData($ThemeName);
    }
  }

  /**
   * Deletes all KVStorage Items from the Plugin
   * 
   * If the theme is installed, it will get uninstalled first
   * @param string $ThemeName The folder name of the theme
   * @return boolean TRUE if the data has been successfully deleted
   */
  public static function deleteData($ThemeName) {

    if (self::isInstalled($ThemeName)) {
      self::uninstall($ThemeName);
      return;
    }

    $ThemeFolder = \crisp\api\Config::get("theme_dir");

    if (!\file_exists(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/theme.json")) {
      return false;
    }

    $ThemeMetadata = json_decode(\file_get_contents(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/theme.json"));

    self::uninstallKVStorage($ThemeMetadata);
    self::uninstallTranslations($ThemeMetadata);
  }

  public static function reinstall($ThemeName) {
    if (!self::uninstall($ThemeName)) {
      return false;
    }
    return self::install($ThemeName);
  }

  public static function getThemeMetadata($ThemeName) {
    $ThemeFolder = \crisp\api\Config::get("theme_dir");

    if (!\file_exists(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/theme.json")) {
      return false;
    }

    return json_decode(\file_get_contents(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/theme.json"));
  }

  public static function uninstallKVStorage($ThemeMetadata) {
    if (!\is_object($ThemeMetadata) && !isset($ThemeMetadata->hookFile)) {
      return false;
    }

    if (isset($ThemeMetadata->onInstall->createKVStorageItems) && \is_object($ThemeMetadata->onInstall->createKVStorageItems)) {
      foreach ($ThemeMetadata->onInstall->createKVStorageItems as $Key => $Value) {
        \crisp\api\Config::delete($Key);
      }
    }
  }

  public static function uninstallTranslations($ThemeMetadata) {
    if (!\is_object($ThemeMetadata) && !isset($ThemeMetadata->hookFile)) {
      return false;
    }
    if (defined("CRISP_CLI")) {
      echo "----------" . PHP_EOL;
      echo "Uninstalling translations" . PHP_EOL;
      echo "----------" . PHP_EOL;
    }
    try {
      $Configs = \crisp\api\Translation::listTranslations();


      $Language = \crisp\api\lists\Languages::getLanguageByCode("en");

      foreach ($Configs as $Key => $Translation) {
        if (strpos($Translation["key"], "plugin_") !== false) {
          continue;
        }

        if (defined("CRISP_CLI")) {
          echo "Deleting key " . $Translation["key"] . PHP_EOL;
        }
        if ($Language->deleteTranslation($Translation["key"])) {
          if (defined("CRISP_CLI")) {
            echo "Deleted Key " . $Translation["key"] . PHP_EOL;
          }
        }
      }
    } catch (\PDOException $ex) {
      
    }
  }

  public static function install($ThemeName) {

    if (\crisp\api\Config::get("theme") !== false && \crisp\api\Config::get("theme") == $ThemeName) {
      return false;
    }

    $ThemeFolder = \crisp\api\Config::get("theme_dir");

    if (!\file_exists(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/theme.json")) {
      echo "No theme.json found!" . PHP_EOL;
      return false;
    }

    $ThemeMetadata = json_decode(\file_get_contents(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/theme.json"));


    self::performOnInstall($ThemeName, $ThemeMetadata);

    if (!\is_object($ThemeMetadata) && !isset($ThemeMetadata->hookFile)) {
      var_dump($ThemeMetadata);
      echo "No hookfile in theme.json found!" . PHP_EOL;
      return false;
    }

    self::broadcastHook("themeInstall_$ThemeName", time());
    self::broadcastHook("themeInstall", $ThemeName);

    return \crisp\api\Config::set("theme", $ThemeName);
  }

  /**
   * Registers an uninstall hook for your theme.
   * @param string $ThemeName
   * @param string|function $Function Callback function, either anonymous or a string to a function
   * @returns boolean TRUE if hook could be registered, otherwise false
   */
  public static function registerUninstallHook($ThemeName, $Function) {
    if (\is_callable($Function) || \function_exists($ThemeName)($Function)) {
      self::on("themeUninstall_$ThemeName", $Function);
      return true;
    }
    return false;
  }

  /**
   * Registers an install hook for your theme.
   * @param string $ThemeName
   * @param string|function $Function Callback function, either anonymous or a string to a function
   * @returns boolean TRUE if hook could be registered, otherwise false
   */
  public static function registerInstallHook($ThemeName, $Function) {
    if (\is_callable($Function) || \function_exists($ThemeName)($Function)) {
      self::on("themeInstall_$ThemeName", $Function);
      return true;
    }
    return false;
  }

}
