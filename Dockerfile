# docker build -t pfinal/mosquitto-prometheus-exporter .
# docker push pfinal/mosquitto-prometheus-exporter
FROM pfinal/php:7.0-apache
COPY . /data
WORKDIR /data

CMD ["php" ,"app.php", "start"]
