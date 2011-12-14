<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <title>Dumperor</title>
        <link rel="stylesheet" type="text/css" href="css/main.css">
    </head>
    <body>
        <?php
        $cfgFiles = array();
        $dir = @opendir('conf');

        if ($dir) {
            while (($file = readdir($dir)) !== false) {
                if ('.ini' == strrchr($file, '.')) {
                    $cfgFiles[] = $file;
                }
            }
            closedir($dir);
        }

        if (count($cfgFiles)>0) {
            echo '<h1>Available configurations:</h1>';
            echo '<ol>';
            foreach ($cfgFiles as $file) {
                echo "<li class=\"config_item\"><b>$file</b>";
                echo "<ol><li><a href=\"dump.php?c=".urlencode(basename($file, '.ini'))."&action=difftable\" target=\"_blank\">Diffable: Table Structure.</a></li>";
                echo "<li><a href=\"dump.php?c=".urlencode(basename($file, '.ini'))."&action=diffdata\" target=\"_blank\">Diffable: Data.</a></li>";
                echo "<li><a href=\"dump.php?c=".urlencode(basename($file, '.ini'))."&action=dumptable\" target=\"_blank\">SQL: Table Structure.</a></li>";
                echo "<li><a href=\"dump.php?c=".urlencode(basename($file, '.ini'))."&action=dumpdata\" target=\"_blank\">SQL: Data.</a></li>";
                echo "</ol></li>";
            }
            echo '</ol>';
        } else {
            echo '<h1>No configurations found !</h1>';
        }
        ?>
    </body>
</html>
