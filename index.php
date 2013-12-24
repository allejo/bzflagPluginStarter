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

if (isset($_POST['generate']))
{
    header("Content-Type: text/plain");

    // Let's make some easy reference variables
    $pluginName = (strlen($_POST['plugin-name']) > 0) ? $_POST['plugin-name'] : "SAMPLE_PLUGIN";
    $author = (strlen($_POST['author']) > 0) ? $_POST['author'] : "John Doe";
    $license = $_POST['license'];
    $slashCommands = preg_split("/[\r\n]+/", $_POST['slashcommands'], -1, PREG_SPLIT_NO_EMPTY);
    $events = $_POST['Events'];
    $bracesLocation = ($_POST['braces'] == "new") ? "\n" : " ";

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
    $generatedPlugin .= sprintf($classHeader, $className, $classInheritance, $bracesLocation, $pluginName, $slashCommandDeclaraction, $className) . "\n\n";

    // Get the init() template
    $initInitialization = file_get_contents('sections/init.txt');
    $registeredEvents = "";
    $registeredSlashCommands = "";

    // Check if we have to handle events in order to register them
    if (count($events) > 0)
    {
        $registeredEvents = "\t// Register our events with Register()";

        foreach ($_POST['Events'] as $event)
        {
            $registeredEvents .= "\n\tRegister(" . $event . ");";
        }
    }

    // Check if we have to handle slash commands to register them
    if (count($slashCommands) > 0)
    {
        $registeredSlashCommands = "\n\n\t// Register our custom slash commands";

        foreach ($slashCommands as $command)
        {
            $registeredSlashCommands .= "\n\tbz_registerCustomSlashCommand('" . $command . "', this);";
        }
    }

    // Add our init() code to the generated code thus far
    $generatedPlugin .= sprintf($initInitialization, $className, $bracesLocation, $registeredEvents, $registeredSlashCommands) . "\n\n";

    // Let's handle the Cleanup() function now
    $cleanupInitialization = file_get_contents('sections/cleanup.txt');
    $cleanupSlashCommands = "";

    // Check if there are any slash commands that we need to handle
    if (count($slashCommands) > 0)
    {
        $cleanupSlashCommands = "\n\n\t// Clean up our custom slash commands";

        foreach ($slashCommands as $command)
        {
            $cleanupSlashCommands .= "\n\tbz_removeCustomSlashCommand('" . $command . "');";
        }
    }

    // Add our cleanup() to the generated code
    $generatedPlugin .= sprintf($cleanupInitialization, $className, $bracesLocation, $cleanupSlashCommands) . "\n\n";

    // Store our events template here for now
    $switchEvent = file_get_contents('sections/event.txt');
    $switchEventCode = "";
    $firstIfStatement = true;

    // If the person choose to use a switch statement, we need to add the code snippet to declare
    // the switch statement
    if ($_POST['eventhandling'] == "switch")
    {
        $switchEventCode .= "\tswitch (eventData->eventType)";
        $switchEventCode .= ($_POST['braces'] == "new") ? "\n\t{\n" : " {\n";
    }

    // Get the data comments for each of the events and add them to the if/switch statement
    // Format them respectively according to choice of the user
    foreach ($events as $event)
    {
        // We need to get all the data for an event so let's get it and store it
        $eventData = file_get_contents('events/' . $event . '.txt');

        // Check to see if we want to put the open brace on the same line as the if statement
        $braces = ($_POST['braces'] == "same") ? "{ " : "";

        // If we want the open brace on the new line, then let's add it to a new line
        $endOfLine = ($_POST['braces'] == "same") ? "" : "\n\t{";

        if ($_POST['eventhandling'] == "if")
        {
            // This will be our default if statement template, if we are not on our first condition,
            // then we will look prepend an "else" to form an "else if" condition
            $defaultIfStatemet = "if (eventData->eventType == " . $event . ")";

            // Check whether or not to form an "else if" condition
            if ($firstIfStatement)
            {
                $firstIfStatement = false;
            }
            else
            {
                $defaultIfStatemet = "\n\telse " . $defaultIfStatemet;
            }

            // Add the generated code
            $switchEventCode .= sprintf($eventData, $defaultIfStatemet, $braces, $endOfLine, "");
        }
        else if ($_POST['eventhandling'] == "switch")
        {
            $formattedCode = sprintf($eventData, "case " . $event . ":", $braces, $endOfLine, "\n\tbreak;\n\n");

            $switchEventCode .= str_replace("\t", "\t\t", $formattedCode);
        }
    }

    // If we're generating a switch statement, then let's add the default case and close the switch statement
    if ($_POST['eventhandling'] == "switch")
    {
        $switchEventCode .= "\t\tdefault: break;\n\t}";
    }

    // Add our switch statement to the generated code
    $generatedPlugin .= sprintf($switchEvent, $className, $bracesLocation, $switchEventCode) . "\n\n";

    // We have slash commands to handle
    if (count($slashCommands) > 0)
    {
        $slashCommandInitialization = file_get_contents('sections/slashcommand.txt');
        $commandIfStatements = "";
        $firstStatement = true;

        foreach ($slashCommands as $command)
        {
            if ($firstStatement)
            {
                $commandIfStatements .= "\tif (command == \"" . $command . '")';
                $firstStatement = false;
            }
            else
            {
                $commandIfStatements .= "\n\telse if (command == \"" . $command . '")';
            }

            $commandIfStatements .= $bracesLocation . (($bracesLocation == "\n") ? "\t" : "") . "{\n\n\t\treturn true;\n\t}";
        }

        $generatedPlugin .= sprintf($slashCommandInitialization, $className, $bracesLocation, $commandIfStatements);
    }

    // The templates used use tabs by default for indentation so we can easily switch them if needed
    // The user has requested to use four or 2 spaces instead, so let's swap all the tabs we have to
    // use spaces instead
    if ($_POST['spacing'] == "four")
    {
        $generatedPlugin = str_replace("\t", "    ", $generatedPlugin);
    }
    else if ($_POST['spacing'] == "two")
    {
        $generatedPlugin = str_replace("\t", "  ", $generatedPlugin);
    }

    echo $generatedPlugin;
    return;
}

$licenses = array();
$events = array();

// Let's read all the license templates that we have
if ($handle = opendir('licenses'))
{
    while (false !== ($entry = readdir($handle)))
    {
        if ($entry != "." && $entry != "..")
        {
            $licenses[] = substr($entry, 0, strrpos($entry, "."));
        }
    }

    closedir($handle);
}

// Let's read all of the events that we have available
if ($handle = opendir('events'))
{
    while (false !== ($entry = readdir($handle)))
    {
        if ($entry != "." && $entry != "..")
        {
            $events[] = substr($entry, 0, strrpos($entry, "."));
        }
    }

    closedir($handle);
}

sort($licenses);
sort($events);

?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="css/styles.css">
        <title>allejo's BZFlag Plugin Starter</title>
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
                        foreach ($licenses as $license)
                        {
                            echo '<option value="' . $license . '">' . $license . '</option>';
                        }
                    ?>
                </select>

                <h2>Plug-in Events</h2>
                <section>
                    <?php
                        foreach ($events as $event)
                        {
                            echo '<p><input type="checkbox" name="Events[]" value="' . $event . '" /> ' . $event . '</p>';
                        }
                    ?>
                </section>

                <h2>Plug-in Slashcommands</h2>
                <p>Insert one slash command per line without the '/'.</p>
                <textarea name="slashcommands"></textarea>

                <h2>Plug-in Source Code Settings</h2>
                <section>
                    <article>
                        <h3>Spacing</h3>
                        <input type="radio" name="spacing" value="two"> 2 Spaces<br>
                        <input type="radio" name="spacing" value="four" checked="true"> 4 Spaces<br>
                        <input type="radio" name="spacing" value="tabs"> Tabs<br>
                    </article>

                    <article>
                        <h3>Event Handling</h3>
                        <input type="radio" name="eventhandling" value="if"> If Statement<br>
                        <input type="radio" name="eventhandling" value="switch" checked="true"> Switch Statement<br>
                    </article>

                    <article>
                        <h3>Braces Placement</h3>
                        <input type="radio" name="braces" value="new" checked="true"> New Line<br>
                        <input type="radio" name="braces" value="same"> Same Line<br>
                    </article>
                </section>

                <input type="hidden" name="generate" value="true" />
                <input type="submit" value="Generate" />
            </form>
        </div>

        <footer>
            Copyright &copy; 2013 - Vladimir "allejo" Jimenez
        </footer>
    </body>
</html>