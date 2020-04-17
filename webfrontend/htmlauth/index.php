<?php
  require_once "loxberry_system.php";
  require_once "loxberry_web.php";
  require_once "Config/Lite.php";

  LBWeb::lbheader("Music Server Interface");

  $cfg = new Config_Lite("$lbpconfigdir/data.cfg", LOCK_EX, INI_SCANNER_RAW);
  $cfg->setQuoteStrings(FALSE);

  if (count(array_keys($_POST)) > 0) {
    for ($id = 1; $id <= $_POST["music-servers"]; $id++) {
      $_POST["music-server-$id-zones"] = array_key_exists("music-server-$id-zones", $_POST)
        ? intval($_POST["music-server-$id-zones"])
        : 4;

      $_POST["music-server-$id-miniserver"] = array_key_exists("music-server-$id-miniserver", $_POST)
        ? intval($_POST["music-server-$id-miniserver"])
        : 1;

      $_POST["music-server-$id-receivers"] = array_key_exists("music-server-$id-receivers", $_POST)
        ? $_POST["music-server-$id-receivers"]
        : '';
    }

    foreach ($_POST as $key => $value) {
      $cfg->set("data", $key, "$value");
    }

    reboot_required("A reboot is required to apply the new configuration!");
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
    width: 1px;
    padding-right: .5em;
    white-space: nowrap;
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
</style>

<?php if (count(array_keys($_POST)) > 0) { ?>
  <div class="warning">
    A reboot is required to apply the new configuration!
  </div>
<?php } ?>

<form method="POST">
  <div class="key-value">
    <dl>
      <dt>
        Music servers:
      </dt>

      <dd>
        <input type="text" name="music-servers" value="<?= $cfg["data"]["music-servers"] ?>" />
      </dd>
    </dl>
  </div>

  <?php for ($id = 1; $id <= $cfg["data"]["music-servers"]; $id++) { ?>
    <?php
      $vi = $vo = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
      $port = 6090 + $id;

      $vi .= "<VirtualInUdp Title=\"Music server $id\" Comment=\"\" Address=\"\" Port=\"$port\">";
      $vo .= "<VirtualOut Title=\"Music server $id\" Comment=\"\" Address=\"/dev/udp/&lt;LOXBERRY_IP&gt;/$port\" CmdInit=\"\" CloseAfterSend=\"false\" CmdSep=\";\">";

      for ($zone = 1; $zone <= $cfg["data"]["music-server-$id-zones"]; $zone++) {
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

      $vi .= "</VirtualInUdp>";
      $vo .= "</VirtualOut>";
    ?>

    <div class="box">
      <p>
        Music Server <?= $id ?> is located in port <?= 6090 + $id ?>:
      </p>

      <dl>
        <dt>
          Zones:
        </dt>

        <dd>
	  <input type="text" name="music-server-<?= $id ?>-zones" value="<?= $cfg["data"]["music-server-$id-zones"] ?>" />
        <dd>
      </dl>

      <dl>
        <dt>
          Send to miniserver as virtual inputs/outputs:
        </dt>

        <dd>
          <input type="hidden" name="music-server-<?= $id ?>-miniserver" value="0" />
          <input type="checkbox" name="music-server-<?= $id ?>-miniserver" value="1" <?= $cfg["data"]["music-server-$id-miniserver"] ? 'checked' : '' ?> />
        <dd>
      </dl>

      <dl>
        <dt>
          IPs to send messages (comma separated list):
        </dt>

        <dd>
          <input type="text" name="music-server-<?= $id ?>-receivers" value="<?= $cfg["data"]["music-server-$id-receivers"] ?>" />
        <dd>
      </dl>

      <p>
        <a
          class="ui-btn ui-input-btn ui-corner-all ui-shadow ui-icon-arrow-d ui-btn-icon-left"
          href="data:application/octet-stream;charset=utf-8;base64,<?= base64_encode($vi) ?>"
          download="vi-msi-<?= 6090 + $id ?>.xml"
        >
          Get virtual inputs
        </a>

        <a
          class="ui-btn ui-input-btn ui-corner-all ui-shadow ui-icon-arrow-d ui-btn-icon-left"
          href="data:application/octet-stream;charset=utf-8;base64,<?= base64_encode($vo) ?>"
          download="vo-msi-<?= 6090 + $id ?>.xml"
        >
          Get virtual outputs
        </a>
      </p>
    </div>
  <?php } ?>

  <input type="submit" value="Submit" data-icon="check" />
</form>

<?php
  LBWeb::lbfooter();
?>
