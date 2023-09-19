# tradebot-pricerequest-worker
Worker to process file content (rabbitMq) or API calls (HTTP):
    - gather tickData and push to 1s OHLCV series

Pre-requisites: <br />
    - PHP CLI >= v8.1 <br />
    - Shared filesystem with tradebot-pricerequest<br />

Launch worker:
    - $ bin/console messenger:consume processing -vvv