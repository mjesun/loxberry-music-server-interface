<?php
  require_once "loxberry_system.php";
  require_once "loxberry_web.php";
  require_once "Config/Lite.php";

  LBWeb::lbheader("Music Server Interface");

  $cfg = new Config_Lite("$lbpconfigdir/data.cfg", LOCK_EX, INI_SCANNER_RAW);
  $cfg->setQuoteStrings(FALSE);

  if (count(array_keys($_POST)) > 0) {
    for ($id = 1; $id <= $_POST["music-servers"]; $id++) {
      $_POST["music-server-$id-zones"] = intval($_POST["music-server-$id-zones"]) ?? 1;
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
    <?php foreach ($cfg["data"] as $key => $value) { ?>
      <dl>
        <dt>
          <?= ucfirst(preg_replace('/[-_]/', ' ', $key)) ?>:
        </dt>

        <dd>
          <input type="text" name="<?= $key ?>" value="<?= $value ?>" />
        </dd>
      </dl>
    <?php } ?>
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

        <a class="ui-btn ui-input-btn ui-corner-all ui-shadow ui-icon-arrow-d ui-btn-icon-left" href="data:application/octet-stream;charset=utf-8;base64,<?= base64_encode($vi) ?>">
          Get virtual inputs
        </a>

        <a class="ui-btn ui-input-btn ui-corner-all ui-shadow ui-icon-arrow-d ui-btn-icon-left" href="data:application/octet-stream;charset=utf-8;base64,<?= base64_encode($vo) ?>">
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
