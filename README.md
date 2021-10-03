
Development Environment

```
docker run -v $PWD:/data -p 8080:8080 pfinal/php:7.0-apache php /data/app.php start
```


Production Environment

```
docker run -d --restart=always --name mosquitto-exporter -p 9100:9100 \
  --memory=100M --memory-swap=100M --cpus=0.5 \
  -e MQTT_ADDRESS=mqtt://www.example.com:1883 \
  -e MQTT_USERNAME=test -e MQTT_PASSWORD=test \
  -v $PWD/runtime:/data/runtime \
  pfinal/mosquitto-prometheus-exporter
```

```
http://localhost:9100
```
