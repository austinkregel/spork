import test from 'node:test';
import assert from 'node:assert/strict';
import http from 'node:http';
import { Server as IOServer } from 'socket.io';
import { io as ioClient } from 'socket.io-client';

test('agents keepalive: responds to server ping with pong', async () => {
  const server = http.createServer();
  const io = new IOServer(server, { transports: ['websocket'], serveClient: false });

  /** @type {import('socket.io').Namespace} */
  const nsp = io.of('/agents');

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  const { port } = server.address();
  const url = `http://127.0.0.1:${port}`;

  let serverSocket = null;
  const pongPromise = new Promise((resolve) => {
    nsp.on('connection', (sock) => {
      serverSocket = sock;
      sock.on('pong', (payload) => resolve(payload));
    });
  });

  const client = ioClient(`${url}/agents`, { transports: ['websocket'], reconnection: false });
  await new Promise((resolve, reject) => {
    client.on('connect', resolve);
    client.on('connect_error', reject);
  });

  // This mirrors the bridge keepalive handler in `bridge.mjs`.
  client.on('ping', (msg) => {
    client.emit('pong', { ts: Date.now(), ...(msg && typeof msg === 'object' ? { serverTs: msg.ts } : {}) });
  });

  serverSocket.emit('ping', { ts: 123 });
  const pong = await pongPromise;

  assert.equal(typeof pong.ts, 'number');
  assert.equal(pong.serverTs, 123);

  client.disconnect();
  await new Promise((resolve) => io.close(resolve));
  await new Promise((resolve) => server.close(resolve));
});








