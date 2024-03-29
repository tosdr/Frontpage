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

namespace crisp\core;

use crisp\api\Helper;
use crisp\api\lists\Cron;
use crisp\api\Translation;
use crisp\exceptions\BitmaskException;
use stdClass;
use Twig\Environment;

/**
 * Used internally, plugin loader
 *
 */
class Plugin
{

    use Hook;

    public string $PluginFolder;
    public string $PluginName;
    public stdClass $PluginMetadata;
    public string $PluginPath;
    public ?Environment $TwigTheme;
    public string $CurrentFile;
    public string $CurrentPage;

    /**
     *
     * @param string $PluginFolder The path to your plugin
     * @param string $PluginName The name of your plugin
     * @param stdClass $PluginMetadata Plugin.json file contents
     * @param Environment $TwigTheme The current twig theme
     * @param string $CurrentFile The current file
     * @param string $CurrentPage The current $_GET["page"] parameter
     * @throws BitmaskException
     */
    public function __construct(string $PluginFolder, string $PluginName, stdClass $PluginMetadata, ?Environment $TwigTheme, string $CurrentFile, string $CurrentPage)
    {
        $this->PluginFolder = Helper::filterAlphaNum($PluginFolder);
        $this->PluginName = Helper::filterAlphaNum($PluginName);
        $this->PluginMetadata = $PluginMetadata;
        $this->PluginPath = realpath(__DIR__ . '/../../../../' . $PluginFolder . '/' . $PluginName . '/') . '/';
        $this->TwigTheme = $TwigTheme;
        $this->CurrentFile = Helper::filterAlphaNum($CurrentFile);
        $this->CurrentPage = Helper::filterAlphaNum($CurrentPage);
        if (file_exists($this->PluginPath . $PluginMetadata->hookFile)) {
            require $this->PluginPath . $PluginMetadata->hookFile;

            if (($TwigTheme instanceof Environment) && file_exists($this->PluginPath . '/templates/views/' . $this->CurrentPage . '.twig') && file_exists($this->PluginPath . '/includes/views/' . $this->CurrentPage . '.php')) {
                $GLOBALS['plugins'][] = $this;
                require $this->PluginPath . '/includes/views/' . $this->CurrentPage . '.php';


                $_vars = ($_vars ?? []);
                $_vars['plugin'] = $this;

                $GLOBALS['render'][$this->PluginName . '/templates/views/' . $this->CurrentPage . '.twig'] = $_vars;

                unset($_vars);
            }
        } else {
            throw new BitmaskException('Plugin <b>' . $this->PluginName . '</b> failed to load due to a missing includes file', Bitmask::PLUGIN_MISSING_INCLUDES);
        }
    }

    /**
     * @param string $Key
     * @param int $Count
     * @param array $UserOptions
     * @return string
     * @see \crisp\api\Translation::fetch
     */
    public function getTranslation(string $Key, int $Count = 1, array $UserOptions = array()): string
    {

        $Locale = Helper::getLocale();


        $Translation = new Translation($Locale);


        return $Translation->fetch('plugin.' . $this->PluginName . ".$Key", $Count, $UserOptions);
    }

    /**
     * @see \crisp\api\Config::get
     */
    public function getConfig(string $Key): mixed
    {
        return \crisp\api\Config::get('plugin.' . $this->PluginName . ".$Key");
    }

    /**
     * @param string $Type
     * @param $Data
     * @param string $Interval
     * @param bool $ExecuteOnce
     * @return int
     * @see \crisp\api\lists\Cron::create
     */
    public function createCron(string $Type, $Data, string $Interval = '2 MINUTE', bool $ExecuteOnce = false): int
    {
        return Cron::create('execute_plugin_cron', json_encode(array('data' => $Data, 'name' => $Type)), $Interval, $this->PluginName, $ExecuteOnce);
    }

    /**
     * @param string $File
     * @return string
     */
    public function includeResource(string $File): string
    {
        if (str_starts_with($File, '/')) {
            $File = substr($File, 1);
        }

        if (!file_exists(__DIR__ . '/../../../../' . \crisp\api\Config::get('plugin_dir') . '/' . $this->PluginName . "/$File")) {
            return (\crisp\api\Config::exists('cdn') ? \crisp\api\Config::get('cdn') : '') . '/' . \crisp\api\Config::get('plugin_dir') . '/' . $this->PluginName . "/$File";
        }

        return (\crisp\api\Config::exists('cdn') ? \crisp\api\Config::get('cdn') : '') . '/' . \crisp\api\Config::get('plugin_dir') . '/' . $this->PluginName . "/$File?" . hash_file('sha256', __DIR__ . '/../../../../' . \crisp\api\Config::get('plugin_dir') . '/' . $this->PluginName . "/$File");
    }

    /**
     * @param string $Key
     * @return bool
     * @see \crisp\api\Config::delete
     */
    public function deleteConfig(string $Key): bool
    {
        return \crisp\api\Config::delete('plugin_' . $this->PluginName . "_$Key");
    }

    /**
     * @param string $Key
     * @param mixed $Value
     * @return bool
     * @see \crisp\api\Config::set
     */
    public function setConfig(string $Key, mixed $Value): bool
    {
        return \crisp\api\Config::set('plugin_' . $this->PluginName . "_$Key", $Value);
    }

    /**
     * @param string $Key
     * @param mixed $Value
     * @return bool
     * @see \crisp\api\Config::create
     */
    public function createConfig(string $Key, mixed $Value): bool
    {
        return \crisp\api\Config::create('plugin_' . $this->PluginName . "_$Key", $Value);
    }

    /**
     * @return array
     * @see \crisp\core\Plugins::listConfig
     */
    public function listConfig(): array
    {
        return Plugins::listConfig($this->PluginName);
    }

    /**
     * Uninstall a plugin
     * @return bool
     */
    public function uninstall(): bool
    {
        return Plugins::uninstall($this->PluginName, $this->TwigTheme, $this->CurrentFile, $this->CurrentPage);
    }

    /**
     * Check the integrity of a plugin
     * @return array
     */
    public function integrity(): array
    {
        return Plugins::integrityCheck($this->PluginName, $this->PluginMetadata, $this->PluginFolder);
    }

    /**
     * Registers an uninstall hook for your plugin.
     * @param mixed $Function Callback function, either anonymous or a string to a function
     * @return bool
     */
    public function registerUninstallHook(mixed $Function): bool
    {
        return Plugins::registerUninstallHook($this->PluginName, $Function);
    }

    /**
     * Registers an install hook for your plugin.
     * @param mixed $Function Callback function, either anonymous or a string to a function
     * @returns boolean TRUE if hook could be registered, otherwise false
     */
    public function registerInstallHook(mixed $Function): bool
    {
        return Plugins::registerInstallHook($this->PluginName, $Function);
    }

    /**
     * Registers a hook thats called after a template has rendered in your plugin.
     * @param mixed $Function Callback function, either anonymous or a string to a function
     * @returns boolean TRUE if hook could be registered, otherwise false
     */
    public function registerAfterRenderHook(mixed $Function): bool
    {
        return Plugins::registerAfterRenderHook($this->PluginName, $Function);
    }

}
