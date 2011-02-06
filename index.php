<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <title>Dumperor</title>
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
                echo "<li><b>$file</b>";
                echo "<ol><li><a href=\"dump.php?c=".urlencode(basename($file, '.ini'))."&action=tableinfo\" target=\"_blank\">Table structure for comparation.</a></li>";
                echo "<li><a href=\"dump.php?c=".urlencode(basename($file, '.ini'))."&action=datainfo\" target=\"_blank\">Data for comparation.</a></li>";
                echo "<li><a href=\"dump.php?c=".urlencode(basename($file, '.ini'))."&action=tablestmt\" target=\"_blank\">Table structure SQL statements.</a></li>";
                echo "<li><a href=\"dump.php?c=".urlencode(basename($file, '.ini'))."&action=datastmt\" target=\"_blank\">Data SQL statements.</a></li>";
                echo "</ol></li>";
            }
            echo '</ol>';
        } else {
            echo '<h1>No configurations found !</h1>';
        }
        ?>
    </body>
</html>
