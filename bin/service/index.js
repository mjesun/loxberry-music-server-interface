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

const config = cfg.read(path.join('REPLACELBPCONFIGDIR', 'data.cfg'));

for (let id = 0; id < +config.data['music-servers']; id++) {
  const server = new MusicServer({
    zones: +config.data['music-server-' + id + '-zones'],
    port: 6091 + id,
  });

  server.start();
}
