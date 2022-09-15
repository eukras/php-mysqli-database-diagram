#!/usr/bin/bash
php erd.php > dd.dot
fdp -Tsvg -o dd.svg dd.dot
eog dd.svg
