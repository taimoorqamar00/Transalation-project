#!/bin/bash

# Auto-detect which database is available and run tests

echo "🔍 Checking for available databases..."

# Check if Docker containers exist and are running
if sudo docker ps --format '{{.Names}}' 2>/dev/null | grep -q "assessment-digitaltolk_app"; then
    echo "✅ Docker MySQL detected"

    echo "⏳ Waiting for MySQL to be ready inside Docker..."
    # wait for the db container to respond (timeout 60s)
    TIMEOUT=60
    SECONDS_PASSED=0
    until sudo docker-compose exec -T db mysqladmin ping -uroot -proot --silent >/dev/null 2>&1; do
        sleep 1
        SECONDS_PASSED=$((SECONDS_PASSED+1))
        if [ $SECONDS_PASSED -ge $TIMEOUT ]; then
            echo "❌ MySQL did not become ready within ${TIMEOUT}s"
            exit 1
        fi
        echo -n "."
    done
    echo "\n✅ MySQL is ready."

    echo "🧪 Running tests inside Docker container..."
    sudo docker-compose exec -T app bash -lc "php artisan config:clear >/dev/null 2>&1 || true; php artisan test"
    TEST_RESULT=$?

    echo ""
    if [ $TEST_RESULT -eq 0 ]; then
        echo "✅ All tests PASSED with Docker MySQL!"
    else
        echo "❌ Some tests FAILED with Docker MySQL"
    fi
    exit $TEST_RESULT
    
# Check if local MySQL is running
elif sudo systemctl is-active --quiet mysql 2>/dev/null; then
    echo "✅ Local MySQL detected (service: mysql)"
    
    echo "🧪 Running tests with local MySQL..."
    DB_HOST=127.0.0.1 DB_PASSWORD=password php artisan test
    TEST_RESULT=$?
    
    echo ""
    if [ $TEST_RESULT -eq 0 ]; then
        echo "✅ All tests PASSED with local MySQL!"
    else
        echo "❌ Some tests FAILED with local MySQL"
    fi
    exit $TEST_RESULT
    
else
    echo "❌ No MySQL database found!"
    echo ""
    echo "Available options:"
    echo "  1. Start Docker containers:  sudo docker-compose up -d"
    echo "  2. Start local MySQL:        sudo systemctl start mysql"
    exit 1
fi
