#!/bin/bash

# Railway startup wrapper script
# This handles the PORT variable issue at the system level

echo "SafeKeep Railway Startup Wrapper"
echo "Current environment:"
env | grep -E "(PORT|PHP)" || true

# Extract port from command line or environment
if [[ "$*" == *"0.0.0.0:\$PORT"* ]]; then
    echo "Detected Railway's problematic command with \$PORT"
    # Replace the problematic command
    REAL_PORT=${PORT:-8080}
    echo "Fixing PORT variable: \$PORT -> $REAL_PORT"
    NEW_CMD=$(echo "$*" | sed "s/\$PORT/$REAL_PORT/g")
    echo "Executing corrected command: $NEW_CMD"
    exec $NEW_CMD
else
    echo "Executing command as-is: $*"
    exec "$@"
fi