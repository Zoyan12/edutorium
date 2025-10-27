#!/bin/bash
# Maintenance Mode Auto-End Cron Job
# This script should be run every 5 minutes to automatically end maintenance mode
# when the estimated time has passed

# Add this to your crontab:
# */5 * * * * /path/to/your/project/client/auto-end-maintenance-cron.sh

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Run the auto-end maintenance script
php "$SCRIPT_DIR/auto-end-maintenance.php"

# Log the execution (optional)
echo "$(date): Auto-end maintenance check completed" >> "$SCRIPT_DIR/maintenance-cron.log"
