#!/bin/bash
# Log rotation script

LOG_DIR="/home/printserver/remote-printing/logs"
MAX_SIZE="10M"
KEEP_DAYS=30

# Rotate logs based on size
for logfile in $LOG_DIR/*.log; do
    if [ -f "$logfile" ]; then
        size=$(stat -c%s "$logfile")
        if [ $size -gt 10485760 ]; then # 10MB
            mv "$logfile" "$logfile.$(date +%Y%m%d_%H%M%S)"
            touch "$logfile"
            echo "Rotated: $logfile"
        fi
    fi
done

# Delete old log files
find $LOG_DIR -name "*.log.*" -mtime +$KEEP_DAYS -delete

echo "Log rotation completed"
