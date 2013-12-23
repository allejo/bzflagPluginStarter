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
    // Get the plugin name, remove all the white space, and use CamelCase so we
    // can use this as the class name when we generate the plugin
    $className = preg_replace('/\s+/', '', ucwords($_POST['plugin-name']));

    // We need to get the current date to generate the copyright notices at the
    // top of our generated plugin
    $currentYear = date("Y");


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