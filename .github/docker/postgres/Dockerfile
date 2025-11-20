FROM postgres:16-alpine

RUN echo "max_connections = 200" >> /usr/local/share/postgresql/postgresql.conf.sample
RUN echo "shared_buffers = 256MB" >> /usr/local/share/postgresql/postgresql.conf.sample