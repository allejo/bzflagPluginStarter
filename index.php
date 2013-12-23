<?php
/*
BZFlag Plugin Starter
    Copyright (C) 2013 Vladimir "allejo" Jimenez

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (isset($_POST['generate']))
{
    header("Content-Type: text/plain");

    $pluginName = $_POST['plugin-name'];
    $author = $_POST['author'];
    $license = $_POST['license'];
    $slashCommands = preg_split("/[\r\n]+/", $_POST['slashcommands'], -1, PREG_SPLIT_NO_EMPTY);
    $events = $_POST['Events'];

    // Get the plugin name, remove all the white space, and use CamelCase so we
    // can use this as the class name when we generate the plugin
    $className = preg_replace('/\s+/', '', ucwords($pluginName));

    // We need to get the current date to generate the copyright notices at the
    // top of our generated plugin
    $currentYear = date("Y");

    // We will be storing our generated plugin code in this variable and finally
    // output this after we're done
    $generatedPlugin = "/*\n";

    // Let's temporarily store our license templates so we can format it in a bit
    $licenseTemplate = file_get_contents('licenses/' . $license . '.txt');

    // The GPL licenses require a different amount of arguments in the lincense
    // header so we need to accomodate that
    if ($_POST['license'] == "GPLv2" || $_POST['license'] == "GPLv3" ||
        $_POST['license'] == "LGPLv2")
    {
        $generatedPlugin .= sprintf($licenseTemplate, $pluginName, $currentYear, $author);
    }
    else
    {
        $generatedPlugin .= sprintf($licenseTemplate, $currentYear, $author);
    }

    // Clean up our license information by ending the comment
    $generatedPlugin .= "\n*/\n\n";

    // Get the class header with all the declarations we're gonna use
    $classHeader = file_get_contents('sections/class.txt');

    // Declare some default values regardless if we handle slash commands or not
    $classInheritance = "public bz_Plugin";
    $slashCommandDeclaraction = "";

    // Check if we need to handle slash commands or not so we can inherit the
    // proper class and declare the slashcommand event
    if (count($slashCommands) > 0)
    {
        $classInheritance .= ", public bz_CustomSlashCommandHandler";
        $slashCommandDeclaraction = "\n\n    virtual bool SlashCommand (int playerID, bz_ApiString, bz_ApiString, bz_APIStringList*);";
    }

    // Add the class header to the generated code so far
    $generatedPlugin .= sprintf($classHeader, $className, $classInheritance, $pluginName, $slashCommandDeclaraction, $className) . "\n\n";

    // Get the init() template
    $initInitialization = file_get_contents('sections/init.txt');
    $registeredEvents = "";
    $registeredSlashCommands = "";

    // Check if we have to handle events in order to register them
    if (count($events) > 0)
    {
        $registeredEvents = "\n\n    // Register our events with Register()";

        foreach ($_POST['Events'] as $event)
        {
            $registeredEvents .= "\n    Register(" . $event . ");";
        }
    }

    // Check if we have to handle slash commands to register them
    if (count($slashCommands) > 0)
    {
        $registeredSlashCommands = "\n\n    // Register our custom slash commands";

        foreach ($slashCommands as $command)
        {
            $registeredSlashCommands .= "\n    bz_registerCustomSlashCommand('" . $command . "', this);";
        }

        // Add our init() code to the generated code thus far
        $generatedPlugin .= sprintf($initInitialization, $className, $className, $registeredEvents, $registeredSlashCommands) . "\n\n";
    }

    // Check if there are any slash commands that we need to handle
    if (count($slashCommands) > 0)
    {
        // Let's handle the Cleanup() function now
        $cleanupInitialization = file_get_contents('sections/cleanup.txt');
        $cleanupSlashCommands = "\n\n    // Clean up our custom slash commands";

        foreach ($slashCommands as $command)
        {
            $cleanupSlashCommands .= "\n    bz_removeCustomSlashCommand('" . $command . "');";
        }

        // Add our cleanup() to the generated code
        $generatedPlugin .= sprintf($cleanupInitialization, $className, $className, $cleanupSlashCommands) . "\n\n";
    }

    // Store our events template here for now
    $switchEvent = file_get_contents('sections/event.txt');
    $switchEventCode = "";

    // Get the data comments for each of the events and add them to the switch statement
    foreach ($events as $event)
    {
        $switchEventCode .= file_get_contents('events/' . $event . '.txt') . "\n\n";
    }

    // Add our switch statement to the generated code
    $generatedPlugin .= sprintf($switchEvent, $className, $switchEventCode) . "\n\n";

    $slashCommandInitialization = file_get_contents('sections/slashcommand.txt');
    $commandIfStatements = "";

    if (count($slashCommands) > 0)
    {
        $firstStatement = true;

        foreach ($slashCommands as $command)
        {
            if ($firstStatement)
            {
                $commandIfStatements .= '    if (command == "' . $command . '")';
                $commandIfStatements .= "\n    {\n\n    }";
                $firstStatement = false;
            }
            else
            {
                $commandIfStatements .= "\n    else if (command == \"" . $command . '")';
                $commandIfStatements .= "\n    {\n\n    }";
            }
        }
    }

    $generatedPlugin .= sprintf($slashCommandInitialization, $className, $commandIfStatements);

    echo $generatedPlugin;
    return;
}

?>
<html>
    <head>

    </head>
    <body>
        <header>
            <a href="https://github.com/allejo/bzflagPluginStarter">
                <img style="position: absolute; top: 0; right: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_right_red_aa0000.png" alt="Fork me on GitHub">
            </a>
            <h1>BZFlag Plug-in Starter</h1>
        </header>

        <div id="main">
            <form method="post">
                <h2>Plug-in Details</h2>
                <span>Plug-in Name</span>
                <input type="text" name="plugin-name" />
                <span>Author</span>
                <input type="text" name="author" />
                <span>License</span>
                <select name="license">
                    <?php
                        if ($handle = opendir('licenses'))
                        {
                            while (false !== ($entry = readdir($handle)))
                            {
                                if ($entry != "." && $entry != "..")
                                {
                                    $license = substr($entry, 0, strrpos($entry, "."));
                                    echo '<option value="' . $license . '">' . $license . '</option>';
                                }
                            }

                            closedir($handle);
                        }
                    ?>
                </select>
                <h2>Plug-in Events</h2>
                <?php
                    if ($handle = opendir('events'))
                    {
                        while (false !== ($entry = readdir($handle)))
                        {
                            if ($entry != "." && $entry != "..")
                            {
                                $event = substr($entry, 0, strrpos($entry, "."));
                                echo '<input type="checkbox" name="Events[]" value="' . $event . '" /> ' . $event;
                            }
                        }

                        closedir($handle);
                    }
                ?>
                <h2>Plug-in Slashcommands</h2>
                <textarea name="slashcommands"></textarea>
                <input type="hidden" name="generate" value="true" />
                <input type="submit" value="Generate" />
            </form>
        </div>

        <footer>
            Copyright &copy; 2013 - Vladimir "allejo" Jimenez
        </footer>
    </body>
</html>