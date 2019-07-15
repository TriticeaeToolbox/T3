<?php

/*
 * clear temp files from BLAST and downloads
 */

$dir = "/tmp/tht/blast/";

// Clean up old files, older than 10 hours.
system("find $dir -mmin +600 -delete");

echo "Done, removing old BLAST files\n";

$dir = "/tmp/tht";

system("find $dir -mmin +1200 -delete");

echo "Done, removing old download files\n";
