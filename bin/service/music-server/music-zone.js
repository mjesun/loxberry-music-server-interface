'use strict';

module.exports = class MusicZone {
  constructor(musicServer, id) {
    this._id = id;
    this._musicServer = musicServer;

    this._artist = '';
    this._cover = '';
    this._duration = 0;
    this._mode = 'stop';
    this._queueIndex = 0;
    this._repeat = 0;
    this._shuffle = 0;
    this._time = 0;
    this._title = '';
    this._volume = 50;

    this._audioEventSent = false;
    this._playStart = 0;
  }

  audioState() {
    const repeatModes = {0: 0, 2: 1, 1: 3};

    return {
      playerid: this._id,
      album: this.getAlbum(),
      artist: this.getArtist(),
      audiotype: 2,
      coverurl: this.getCover(),
      duration: this.getDuration(),
      mode: this.getMode(),
      plrepeat: repeatModes[this.getRepeat()],
      plshuffle: this.getShuffle(),
      power: 'on',
      station: '',
      time: this.getTime(),
      title: this.getTitle(),
      volume: this.getVolume(),
    };
  }

  getAlbum() {
    return this._album;
  }

  setAlbum(album) {
    this._album = ('' + album).trim();
    this._sendAudioEvent();
  }

  getArtist() {
    return this._artist;
  }

  setArtist(artist) {
    this._artist = ('' + artist).trim();
    this._sendAudioEvent();
  }

  getCover() {
    return this._cover;
  }

  setCover(cover) {
    this._cover = ('' + cover).trim();
    this._sendAudioEvent();
  }

  getDuration() {
    return this._duration;
  }

  setDuration(duration) {
    this._duration = +duration;
    this._sendAudioEvent();
  }

  getMode() {
    return this._mode;
  }

  setMode(mode) {
    if (/^(?:play|pause|stop)$/.test(mode) && this._mode !== mode) {
      this._time = this.getTime();

      if (mode === 'play') {
        this._playStart = Date.now();
      } else {
        this._playStart = 0;
      }

      this._mode = mode;

      this._sendPlayerCommand(mode);
      this._sendAudioEvent();
    }
  }

  getPosition() {
    return this.getTime();
  }

  setPosition(time) {
    this.setTime(time);

    // Position differs from time in the sense that time will not emit an event
    // to the Miniserver, thus avoiding an infinite loop.
    this._sendPlayerCommand('time', this.getTime());
    this._sendAudioEvent();
  }

  getQueueIndex() {
    return this._queueIndex;
  }

  setQueueIndex(queueIndex) {
    this.setPosition(0);

    this._queueIndex = Math.max(0, queueIndex);

    this._sendPlayerCommand('queueIndex', this._queueIndex);
    this._sendAudioEvent();
  }

  getRepeat() {
    return this._repeat;
  }

  setRepeat(repeat) {
    if (repeat === 0 || repeat === 1 || repeat === 2) {
      this._repeat = repeat;

      this._sendPlayerCommand('repeat', repeat);
      this._sendAudioEvent();
    }
  }

  getShuffle() {
    return this._shuffle;
  }

  setShuffle(shuffle) {
    if (shuffle === 0 || shuffle === 1) {
      this._shuffle = shuffle;

      this._sendPlayerCommand('shuffle', shuffle);
      this._sendAudioEvent();
    }
  }

  getTitle() {
    return this._title;
  }

  setTitle(title) {
    this._title = ('' + title).trim();
  }

  getTime() {
    const delta = this._mode === 'play' ? Date.now() - this._playStart : 0;

    return Math.min(this._time + delta / 1000, this._duration);
  }

  setTime(time) {
    this._playStart = this._mode === 'play' ? Date.now() : 0;
    this._time = +time;
  }

  getVolume() {
    return this._volume;
  }

  setVolume(volume) {
    this._volume = Math.min(Math.max(+volume, 0), 100);

    this._sendPlayerCommand('volume', this._volume);
    this._sendAudioEvent();
  }

  _sendPlayerCommand(command, ...args) {
    this._musicServer.pushPlayerState(this._id, command, args);
  }

  _sendAudioEvent() {
    if (!this._audioEventSent) {
      this._audioEventSent = true;

      setTimeout(() => {
        this._audioEventSent = false;
        this._musicServer.pushAudioEvent(this._id);
      }, 25);
    }
  }
};
