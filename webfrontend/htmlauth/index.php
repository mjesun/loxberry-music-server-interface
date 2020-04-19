<?php
  require_once 'loxberry_system.php';
  require_once 'loxberry_web.php';
  require_once 'loxberry_log.php';
  require_once 'Config/Lite.php';

  // This will read your language files to the array $L.
  $L = LBSystem::readlanguage('language.ini');
  $template_title = $L['SETTINGS.PLUGINNAME'];
  $helplink = 'https://www.loxwiki.eu/x/w4AxB';
  $helptemplate = 'help.html';

  // Now output the header.
  LBWeb::lbheader($template_title, $helplink, $helptemplate);

  // Read config.
  $cfg = new Config_Lite("$lbpconfigdir/data.cfg", LOCK_EX, INI_SCANNER_RAW);
  $cfg->setQuoteStrings(false);

  if (count(array_keys($_POST)) > 0) {
    for ($id = 1; $id <= $_POST['music-servers']; $id++) {
      $_POST["music-server-$id-zones"] =
        array_key_exists("music-server-$id-zones", $_POST)
          ? intval($_POST["music-server-$id-zones"])
          : 4;

      $_POST["music-server-$id-miniserver"] =
        array_key_exists("music-server-$id-miniserver", $_POST)
          ? intval($_POST["music-server-$id-miniserver"])
          : 1;

      $_POST["music-server-$id-receivers"] =
        array_key_exists("music-server-$id-receivers", $_POST)
          ? $_POST["music-server-$id-receivers"]
          : '';
    }

    foreach ($_POST as $key => $value) {
      $cfg->set('data', $key, "$value");
    }

    reboot_required($L['SETTINGS.HINT_REBOOT']);
    notify($lbpplugindir, 'settings', $L['SETTINGS.HINT_REBOOT']);
  }

  $cfg->save();
?>

<style>
  .warning {
    background: #ffffe6;
    border: 1px dotted red;
    margin: 0 auto;
    padding: 1em;
    width: 60%;
  }

  .key-value {
    display: table;
    width: 100%;
  }

  dl {
    display: table-row;
  }

  dt {
    padding-right: .5em;
    white-space: nowrap;
    width: 1px;
  }

  dt, dd {
    display: table-cell;
    padding-bottom: .5em;
    padding-top: .5em;
    vertical-align: middle;
  }

  .box {
    border: 1px solid black;
    margin: 1em 0;
    padding: 1em;
  }

  .lb_flex-item {
    flex-wrap: nowrap;
    margin-top: -10px;
    max-width: 450px;
    min-width: 450px;
    width: 450px;
  }

  .lb_flex-item-help {
    margin-left: 10px;
    min-width: 100px;
    position: relative;
    width: 100%;
  }
</style>

<?php
  echo LBLog::get_notifications_html(LBPPLUGINDIR, 'settings');
?>

<center>
  <hr />

  <?= $L['SETTINGS.LABEL_SERVERSTATUS'] ?>&nbsp;

  <?php
    exec('pgrep -f music-server-interface/service/index.js', $output, $return);
  ?>

  <?php if ($return == 0) { ?>
    <span style="color:green; font-weight: bold;"><?= $L['SETTINGS.LABEL_RUNNING'] ?></span>
  <?php } else { ?>
    <span style="color:red; font-weight: bold;"><?= $L['SETTINGS.LABEL_NOTRUNNING'] ?></span>
  <?php } ?>

  &nbsp;&nbsp;

  <a href="#" class="ui-btn ui-btn-inline ui-mini" target="_blank"><?= $L['SETTINGS.BUTTON_START'] ?></a>
  <a href="#" class="ui-btn ui-btn-inline ui-mini" target="_blank"><?= $L['SETTINGS.BUTTON_STOP'] ?></a>
  <a href="#" class="ui-btn ui-btn-inline ui-mini" target="_blank"><?= $L['SETTINGS.BUTTON_RESTART'] ?></a>

  <hr />
</center>

<p>&nbsp;</p>

<form method="POST">
  <div class="lb_flex-container">
    <div class="lb_flex-item-label">
      <label class="control-label"><?= $L['SETTINGS.LABEL_MUSICSERVERS'] ?></label>
    </div>

    <div class="lb_flex-item-spacer"></div>

    <div class="lb_flex-item">
      <fieldset data-role="controlgroup" data-type="horizontal">
        <input type="radio" name="music-servers" id="music-servers1" value="1" />
        <label for="music-servers1"><?= $L['SETTINGS.LABEL_ONE'] ?></label>
        <input type="radio" name="music-servers" id="music-servers2" value="2" />
        <label for="music-servers2"><?= $L['SETTINGS.LABEL_TWO'] ?></label>
        <input type="radio" name="music-servers" id="music-servers3" value="3" />
        <label for="music-servers3"><?= $L['SETTINGS.LABEL_THREE'] ?></label>
        <input type="radio" name="music-servers" id="music-servers4" value="4" />
        <label for="music-servers4"><?= $L['SETTINGS.LABEL_FOUR'] ?></label>
        <input type="radio" name="music-servers" id="music-servers5" value="5" />
        <label for="music-servers5"><?= $L['SETTINGS.LABEL_FIVE'] ?></label>
      </fieldset>

      <script>
        $('input[id=music-servers<?= $cfg['data']['music-servers'] ?>]').prop('checked', true);
      </script>
    </div>

    <div class="lb_flex-item-spacer"></div>

    <div class="lb_flex-item-help hint">
      <?= $L['SETTINGS.HINT_MUSICSERVERS'] ?>
    </div>

    <div class="lb_flex-item-spacer"></div>
  </div>

  <?php for ($id = 1; $id <= $cfg['data']['music-servers']; $id++) { ?>
    <?php
      $vi = $vo = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
      $port = 6090 + $id;

      $vi .= "<VirtualInUdp Title=\"Music server $id\" Comment=\"\" Address=\"\" Port=\"$port\">";
      $vo .= "<VirtualOut Title=\"Music server $id\" Comment=\"\" Address=\"/dev/udp/&lt;LOXBERRY_IP&gt;/$port\" CmdInit=\"\" CloseAfterSend=\"false\" CmdSep=\";\">";

      for ($zone = 1; $zone <= $cfg['data']["music-server-$id-zones"]; $zone++) {
        $vi .= "<VirtualInUdpCmd Title=\"Zone $zone, play\" Comment=\"\" Address=\"\" Check=\"$zone::play\" Signed=\"true\" Analog=\"false\" SourceValLow=\"0\" DestValLow=\"0\" SourceValHigh=\"1\" DestValHigh=\"1\" DefVal=\"0\" MinVal=\"-10000\" MaxVal=\"10000\" />";
        $vi .= "<VirtualInUdpCmd Title=\"Zone $zone, pause\" Comment=\"\" Address=\"\" Check=\"$zone::pause\" Signed=\"true\" Analog=\"false\" SourceValLow=\"0\" DestValLow=\"0\" SourceValHigh=\"1\" DestValHigh=\"1\" DefVal=\"0\" MinVal=\"-10000\" MaxVal=\"10000\" />";
        $vi .= "<VirtualInUdpCmd Title=\"Zone $zone, volume\" Comment=\"\" Address=\"\" Check=\"$zone::volume::\\v\" Signed=\"true\" Analog=\"true\" SourceValLow=\"0\" DestValLow=\"0\" SourceValHigh=\"1\" DestValHigh=\"1\" DefVal=\"0\" MinVal=\"-10000\" MaxVal=\"10000\" />";
        $vi .= "<VirtualInUdpCmd Title=\"Zone $zone, time\" Comment=\"\" Address=\"\" Check=\"$zone::time::\\v\" Signed=\"true\" Analog=\"true\" SourceValLow=\"0\" DestValLow=\"0\" SourceValHigh=\"1\" DestValHigh=\"1\" DefVal=\"0\" MinVal=\"-10000\" MaxVal=\"10000\"/>";
        $vi .= "<VirtualInUdpCmd Title=\"Zone $zone, repeat\" Comment=\"\" Address=\"\" Check=\"$zone::repeat::\\v\" Signed=\"true\" Analog=\"false\" SourceValLow=\"0\" DestValLow=\"0\" SourceValHigh=\"1\" DestValHigh=\"1\" DefVal=\"0\" MinVal=\"-10000\" MaxVal=\"10000\"/>";
        $vi .= "<VirtualInUdpCmd Title=\"Zone $zone, shuffle\" Comment=\"\" Address=\"\" Check=\"$zone::shuffle::\\v\" Signed=\"true\" Analog=\"false\" SourceValLow=\"0\" DestValLow=\"0\" SourceValHigh=\"1\" DestValHigh=\"1\" DefVal=\"0\" MinVal=\"-10000\" MaxVal=\"10000\"/>";
        $vi .= "<VirtualInUdpCmd Title=\"Zone $zone, queue index\" Comment=\"\" Address=\"\" Check=\"$zone::queueIndex::\\v\" Signed=\"true\" Analog=\"true\" SourceValLow=\"0\" DestValLow=\"0\" SourceValHigh=\"1\" DestValHigh=\"1\" DefVal=\"0\" MinVal=\"-10000\" MaxVal=\"10000\" />";

        $vo .= "<VirtualOutCmd Title=\"Zone $zone, push title\" Comment=\"\" CmdOnMethod=\"GET\" CmdOffMethod=\"GET\" CmdOn=\"$zone::setTitle::&lt;v&gt;\" CmdOnHTTP=\"\" CmdOnPost=\"\" CmdOff=\"\" CmdOffHTTP=\"\" CmdOffPost=\"\" Analog=\"true\" Repeat=\"0\" RepeatRate=\"0\" />";
        $vo .= "<VirtualOutCmd Title=\"Zone $zone, push album\" Comment=\"\" CmdOnMethod=\"GET\" CmdOffMethod=\"GET\" CmdOn=\"$zone::setAlbum::&lt;v&gt;\" CmdOnHTTP=\"\" CmdOnPost=\"\" CmdOff=\"\" CmdOffHTTP=\"\" CmdOffPost=\"\" Analog=\"true\" Repeat=\"0\" RepeatRate=\"0\" />";
        $vo .= "<VirtualOutCmd Title=\"Zone $zone, push artist\" Comment=\"\" CmdOnMethod=\"GET\" CmdOffMethod=\"GET\" CmdOn=\"$zone::setArtist::&lt;v&gt;\" CmdOnHTTP=\"\" CmdOnPost=\"\" CmdOff=\"\" CmdOffHTTP=\"\" CmdOffPost=\"\" Analog=\"true\" Repeat=\"0\" RepeatRate=\"0\" />";
        $vo .= "<VirtualOutCmd Title=\"Zone $zone, push cover\" Comment=\"\" CmdOnMethod=\"GET\" CmdOffMethod=\"GET\" CmdOn=\"$zone::setCover::&lt;v&gt;\" CmdOnHTTP=\"\" CmdOnPost=\"\" CmdOff=\"\" CmdOffHTTP=\"\" CmdOffPost=\"\" Analog=\"true\" Repeat=\"0\" RepeatRate=\"0\" />";
        $vo .= "<VirtualOutCmd Title=\"Zone $zone, push duration\" Comment=\"\" CmdOnMethod=\"GET\" CmdOffMethod=\"GET\" CmdOn=\"$zone::setDuration::&lt;v&gt;\" CmdOnHTTP=\"\" CmdOnPost=\"\" CmdOff=\"\" CmdOffHTTP=\"\" CmdOffPost=\"\" Analog=\"true\" Repeat=\"0\" RepeatRate=\"0\" />";
        $vo .= "<VirtualOutCmd Title=\"Zone $zone, push time\" Comment=\"\" CmdOnMethod=\"GET\" CmdOffMethod=\"GET\" CmdOn=\"$zone::setTime::&lt;v&gt;\" CmdOnHTTP=\"\" CmdOnPost=\"\" CmdOff=\"\" CmdOffHTTP=\"\" CmdOffPost=\"\" Analog=\"true\" Repeat=\"0\" RepeatRate=\"0\" />";
      }

      $vi .= '</VirtualInUdp>';
      $vo .= '</VirtualOut>';
    ?>

    <div class="box">
      <p>
        <b>
          <font size="+2">
            <?= $L['SETTINGS.LABEL_MUSICSERVERTITLE'] ?> <?= $id ?>
          </font>
        </b>

        <a
          href="http://<?= LBSystem::get_localip() ?>:<?= 6090 + $id ?>/audio/cfg/all"
          class="ui-btn ui-btn-inline ui-mini"
          target="_blank"
        >
          <?= $L['SETTINGS.BUTTON_TEST'] ?>
        </a>

        <hr />
      </p>

      <div class="lb_flex-container">
        <div class="lb_flex-item-label">
          <label class="control-label">
            <?= $L['SETTINGS.LABEL_PORT'] ?>
          </label>
        </div>

        <div class="lb_flex-item-spacer"></div>

        <div class="lb_flex-item">
          <b><?= 6090 + $id ?></b>
        </div>

        <div class="lb_flex-item-spacer"></div>

        <div class="lb_flex-item-help hint">
          <?= $L['SETTINGS.HINT_PORT'] ?>
        </div>

        <div class="lb_flex-item-spacer"></div>
      </div>

      <div class="lb_flex-container">
        <div class="lb_flex-item-label">
          <label class="control-label">
            <?= $L['SETTINGS.LABEL_ADDRESS'] ?>
          </label>
        </div>

        <div class="lb_flex-item-spacer"></div>

        <div class="lb_flex-item">
          <b><pre>http://<?= LBSystem::get_localip() ?>:<?= 6090 + $id ?></pre></b>
        </div>

        <div class="lb_flex-item-spacer"></div>

        <div class="lb_flex-item-help hint">
          <?= $L['SETTINGS.HINT_ADDRESS'] ?>
        </div>

        <div class="lb_flex-item-spacer"></div>
      </div>

      <div class="lb_flex-container">
        <div class="lb_flex-item-label">
          <label class="control-label">
            <?= $L['SETTINGS.LABEL_ZONES'] ?>
          </label>
        </div>

        <div class="lb_flex-item-spacer"></div>

        <div class="lb_flex-item">
          <input
            type="range"
            min="1"
            max="20"
            name="music-server-<?= $id ?>-zones"
            value="<?= $cfg['data']["music-server-$id-zones"] ?>"
          />
        </div>

        <div class="lb_flex-item-spacer"></div>

        <div class="lb_flex-item-help hint">
          <?= $L['SETTINGS.HINT_ZONES'] ?>
        </div>

        <div class="lb_flex-item-spacer"></div>
      </div>

      <div class="lb_flex-container">
        <div class="lb_flex-item-label">
          <label class="control-label">
            <?= $L['SETTINGS.LABEL_SENDTOMINISERVER'] ?>
          </label>
        </div>

        <div class="lb_flex-item-spacer"></div>

        <div class="lb_flex-item">
          <input
            type="hidden"
            name="music-server-<?= $id ?>-miniserver"
            value="0"
          />

          <input
            type="checkbox"
            data-role="flipswitch"
            name="music-server-<?= $id ?>-miniserver"
            value="1"
            <?= $cfg['data']["music-server-$id-miniserver"] ? 'checked' : '' ?>
          />
        </div>

        <div class="lb_flex-item-spacer"></div>

        <div class="lb_flex-item-help hint">
          <?= $L['SETTINGS.HINT_SENDTOMINISERVER'] ?>
        </div>

        <div class="lb_flex-item-spacer"></div>
      </div>

      <div class="lb_flex-container">
        <div class="lb_flex-item-label">
          <label class="control-label">
            <?= $L['SETTINGS.LABEL_IPTOSENDMESSAGES'] ?>
          </label>
        </div>

        <div class="lb_flex-item-spacer"></div>

        <div class="lb_flex-item">
          <input
            type="text"
            name="music-server-<?= $id ?>-receivers"
            value="<?= $cfg['data']["music-server-$id-receivers"] ?>"
          />
        </div>

        <div class="lb_flex-item-spacer"></div>

        <div class="lb_flex-item-help hint">
          <?= $L['SETTINGS.HINT_IPTOSENDMESSAGES'] ?>
        </div>

        <div class="lb_flex-item-spacer"></div>
      </div>

      <div class="lb_flex-container">
        <div class="lb_flex-item-label">
          <label class="control-label">
            <?= $L['SETTINGS.LABEL_VIRTUALINOUT'] ?>
          </label>
        </div>

        <div class="lb_flex-item-spacer"></div>

        <div class="lb_flex-item">
          <a
            class="ui-btn ui-input-btn ui-corner-all ui-shadow ui-icon-arrow-d ui-btn-icon-left ui-btn-inline ui-mini"
            href="data:application/octet-stream;charset=utf-8;base64,<?= base64_encode($vi) ?>"
            download="vi-msi-<?= 6090 + $id ?>.xml"
          >
            <?= $L['SETTINGS.BUTTON_VIRTUALIN'] ?>
          </a>

          <a
            class="ui-btn ui-input-btn ui-corner-all ui-shadow ui-icon-arrow-d ui-btn-icon-left ui-btn-inline ui-mini"
            href="data:application/octet-stream;charset=utf-8;base64,<?= base64_encode($vo) ?>"
            download="vo-msi-<?= 6090 + $id ?>.xml"
          >
            <?= $L['SETTINGS.BUTTON_VIRTUALOUT'] ?>
          </a>
        </div>

        <div class="lb_flex-item-spacer"></div>

        <div class="lb_flex-item-help hint">
          <?= $L['SETTINGS.HINT_VIRTUALINOUT'] ?>
        </div>

        <div class="lb_flex-item-spacer"></div>
      </div>
    </div>
  <?php } ?>

  <p>&nbsp;</p>

  <p>
    <input type="submit" value="<?= $L['SETTINGS.BUTTON_SUBMIT'] ?>" data-icon="check" />
  </p>
</form>

<?php
  LBWeb::lbfooter();
?>
