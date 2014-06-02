<?php
/*
BZFlag Plugin Starter
    Copyright (C) 2013-2014 Vladimir "allejo" Jimenez

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

if (isset($_POST['submitted']))
{
    // Let's make some easy reference variables
    $pluginName = (strlen($_POST['plugin-name']) > 0) ? $_POST['plugin-name'] : "SAMPLE_PLUGIN";
    $author = (strlen($_POST['author']) > 0) ? $_POST['author'] : "John Doe";
    $license = $_POST['license'];
    $slashCommands = preg_split("/[\r\n]+/", $_POST['slashcommands'], -1, PREG_SPLIT_NO_EMPTY);
    $events = $_POST['Events'];
    $customFlags = array(
                       "abbr" => $_POST['FlagAbbr'],
                       "name" => $_POST['FlagFullName'],
                       "desc" => $_POST['FlagDescription'],
                       "type" => $_POST['FlagType']
                   );
    $bracesLocation = ($_POST['braces'] == "new") ? "\n" : " ";
    $disableApiDocs = ($_POST['disableApiDocs'] == "true");
    $disableCodeComments = ($_POST['disableCodeComments'] == "true");

    // Get the plugin name, remove all the white space, and use CamelCase so we
    // can use this as the class name when we generate the plugin. We also need
    // to remove any strange symbols and replace all numbers with their literal
    // equivalent by setting up an array of replacements
    $numericValues = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
    $literalValues = array('Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine');

    $className = preg_replace("/[^A-Za-z0-9_]/", '', $pluginName);
    $className = str_replace($numericValues, $literalValues, $className);
    $className = preg_replace('/\s+/', '', ucwords($className));

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
        $slashCommandDeclaraction = "\n\n\tvirtual bool SlashCommand (int playerID, bz_ApiString, bz_ApiString, bz_APIStringList*);";
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
        $registeredSlashCommands = (count($events) > 0) ? "\n\n" : "";
        $registeredSlashCommands .= "\t// Register our custom slash commands";

        foreach ($slashCommands as $command)
        {
            $registeredSlashCommands .= "\n\tbz_registerCustomSlashCommand(\"" . $command . "\", this);";
        }
    }

    if (count($customFlags['abbr']) > 0 && !empty($customFlags['abbr'][0]))
    {
        $registeredFlags = (count($events) > 0 || count($slashCommands) > 0) ? "\n\n" : "";
        $registeredFlags .= "\t// Register our custom flags";

        for ($i = 0; $i < count($customFlags['abbr']); $i++)
        {
            $registeredFlags .= "\n\tbz_RegisterCustomFlag(\"" . $customFlags['abbr'][$i] . "\", \"" . $customFlags['name'][$i] . "\", \"" . $customFlags['desc'][$i] . "\", 0, " . $customFlags['type'][$i] . ");";
        }
    }

    // Add our init() code to the generated code thus far
    $generatedPlugin .= sprintf($initInitialization, $className, $bracesLocation, $registeredEvents, $registeredSlashCommands, $registeredFlags) . "\n\n";

    // Let's handle the Cleanup() function now
    $cleanupInitialization = file_get_contents('sections/cleanup.txt');
    $cleanupSlashCommands = "";

    // Check if there are any slash commands that we need to handle
    if (count($slashCommands) > 0)
    {
        $cleanupSlashCommands = "\n\n\t// Clean up our custom slash commands";

        foreach ($slashCommands as $command)
        {
            $cleanupSlashCommands .= "\n\tbz_removeCustomSlashCommand(\"" . $command . "\");";
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

        // We want to disable the API documentations so we'll ignore them
        if ($disableApiDocs)
        {
            $explodedEventData = explode("\n", $eventData); // Explode the event data based on new lines
            $modifiedEventData = ""; // We'll be storing the modified event data without comments

            // Go through each line of the event data
            foreach ($explodedEventData as $line)
            {
                // If it's an empty line or has API documentation, we'll ignore it; otherwise save it
                if (strpos($line, "\t//") === false && !empty($line))
                {
                    $modifiedEventData[] = $line;
                }
            }

            // Imploded the modified event data without empty lines or API documentation and have new lines
            $eventData = implode("\n", $modifiedEventData);
        }

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
            // Get the event data and format the case block as requested
            $formattedCode = sprintf($eventData, "case " . $event . ":", $braces, $endOfLine, "\n\tbreak;\n\n");

            // Because of lack of indentation, we need to the case block one more time so replace
            // a single tab with two tabs
            $formattedCode = str_replace("\t", "\t\t", $formattedCode);

            // Because we replaced all the single tabs with two tabs, we over indented the case
            // block content, so we need to unindent once so replace 4 tabs with 3
            $switchEventCode .= str_replace("\t\t\t\t", "\t\t\t", $formattedCode);
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

    // Check whether we would like to generate the plugin in the browser or to download it to a file
    if ($_POST['generate_button'])
    {
        header("Content-Type: text/plain");
    }
    else if ($_POST['download_button'])
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-disposition: attachment; filename=' . $className . '.cpp');
        header('Content-Length: ' . strlen($generatedPlugin));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Pragma: public');
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
        <link href="//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
        <title>allejo's BZFlag Plugin Starter</title>
        <meta name="description" content="Easily generate the structure for a BZFlag plugin in C++.">
        <meta name="keywords" content="bzflag,plugin,c++,code,skeleton">
        <meta name="author" content="Vladimir Jimenez">
        <meta charset="UTF-8">
    </head>

    <body>
        <header>
            <a href="https://github.com/allejo/bzflagPluginStarter">
                <img style="position: absolute; top: 0; right: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_right_red_aa0000.png" alt="Fork me on GitHub">
            </a>
            <h1>BZFlag Plug-in Starter</h1>
        </header>

        <main>
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
                <section class="events">
                    <?php
                        foreach ($events as $event)
                        {
                            echo '<p>';
                            echo '<input id="evt_' . $event . '" type="checkbox" name="Events[]" value="' . $event . '" />';
                            echo '<label for="evt_' . $event . '">' . $event . '</label>';
                            echo '(<a href="http://wiki.bzflag.org/' . $event . '" target="_blank">?</a>)';
                            echo '</p>';
                        }
                    ?>
                </section>

                <h2>Plug-in Slashcommands</h2>
                <p>Insert one slash command per line without the '/'.</p>
                <textarea name="slashcommands"></textarea>

                <h2>Plug-in Custom Flags</h2>
                <section class="flags">
                    <ul class="help">
                        <li><span>Abbr</span> - The flag abbreviation used for the scoreboard and plug-ins</li>
                        <li><span>Full name</span> - The full name of the flag that will appear in the console and HUD</li>
                        <li><span>Description</span> - A description of what the flag does, which will appear when a player has flag help messages turned on</li>
                        <li><span>Flag type</span> - Either good or bad flag</li>
                    </ul>

                    <article class="custom_flag">
                        <i class="fa fa-plus-circle"></i>
                        <i class="fa fa-minus-circle"></i>
                        <input type="text" class="abbr" maxlength="2" name="FlagAbbr[]" placeholder="Abbr">
                        <input type="text" class="name" name="FlagFullName[]" placeholder="Full name">
                        <input type="text" class="desc" name="FlagDescription[]" placeholder="Description">
                        <select name="FlagType[]">
                            <option value="eGoodFlag">Good Flag</option>
                            <option value="eBadFlag">Bad Flag</option>
                        </select>
                    </article>
                </section>


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

                    <article>
                        <h3>Misc</h3>
                        <input type="checkbox" id="disableApiDocs" name="disableApiDocs" value="true"> <label for="disableApiDocs">Disable API documentation</label><br>
                        <input type="checkbox" id="disableCodeComments" name="disableCodeComments" value="true"> <label for="disableCodeComments">Disable code comments</label>
                    </article>
                </section>

                <div class="buttons">
                    <input type="hidden" name="submitted" value="true" />
                    <input type="submit" name="generate_button" value="Generate" />
                    <input type="submit" name="download_button" value="Download" />
                </div>
            </form>
        </main>

        <footer>
            Copyright &copy; 2013-2014
            <br />
            <small>Vladimir "allejo" Jimenez</small>
        </footer>

        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
        <script>
            $(function()
            {
                $(document).on("click", ".fa-plus-circle", function(e)
                {
                    $(".custom_flag:first").clone().appendTo($(this).parent().parent());
                    e.preventDefault();
                });

                $(document).on("click", ".fa-minus-circle", function(e)
                {
                    if ($(".fa-minus-circle").length > 1)
                    {
                        $(this).parent().remove();
                        e.preventDefault();
                    }
                });
            });
        </script>
    </body>
</html>
