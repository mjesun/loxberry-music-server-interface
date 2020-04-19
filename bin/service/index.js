'use strict';

const http = require('http');
const path = require('path');
const querystring = require('querystring');

const MusicServer = require('./music-server');

const cfg = require('./cfg');
const restart = require('./handlers/restart');

const headers = {
  'Content-Type': 'text/plain; charset=utf-8',
};

Error.stackTraceLimit = Infinity;

http
  .createServer(async (req, res) => {
    try {
      const index = req.url.indexOf('?');
      const url = index === -1 ? req.url : req.url.substr(0, index);
      const query = querystring.parse(req.url.substr(url.length + 1));

      switch (true) {
        case /\/restart(?:\/|$)/.test(url):
          res.writeHead(200, headers);
          res.end(await restart(url, query));
          break;

        default:
          res.writeHead(404, headers);
          res.end();
          break;
      }
    } catch (err) {
      res.writeHead(500, headers);
      res.end(err.stack);
    }
  })
  .listen(6090);

let config = null;

try {
  config = cfg.read(path.join('REPLACELBPCONFIGDIR', 'data.cfg'));
} catch (err) {
  config = {data: {'music-servers': 1, 'music-server-1-zones': 4}};
}

for (let id = 1; id <= +config.data['music-servers']; id++) {
  const port = 6090 + id;

  const key = (name) => {
    return config.data['music-server-' + id + '-' + name];
  };

  const receivers = key('receivers')
    .trim()
    .split(/\s*,\s*/g)
    .map((receiver) => {
      const [gotIp, gotPort] = receiver.split(':');

      return {
        ip: gotIp,
        port: gotPort || port + 1000,
      };
    });

  const server = new MusicServer({
    port,
    receivers,
    miniserver: !!+key('miniserver'),
    zones: +key('zones'),
  });

  server.start();
}
