#!/bin/bash

# Truncate the messenger worker log file
truncate -s 0 /var/www/vhosts/bicgraphic.com/news.bicgraphic.com/var/logs/messenger_worker.log

# Restart all mautic messenger worker services individually
for i in 1 2 3; do
    systemctl --user restart mautic-messenger-worker@${i}.service
done

echo "Log truncated and all worker services restarted."
