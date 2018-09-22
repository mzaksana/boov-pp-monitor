<!DOCTYPE html>
<html lang="en">

<head>
    <title>Infest PaidPromote Monitor App</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <style>
        /* Remove the navbar's default margin-bottom and rounded borders */
    .navbar {
      margin-bottom: 0;
      border-radius: 0;
    }

    /* Set height of the grid so .sidenav can be 100% (adjust as needed) */
    .row.content {height: 450px}

    /* Set gray background color and 100% height */
    .sidenav {
      padding-top: 20px;
      background-color: #f1f1f1;
      height: 100%;
    }

    /* Set black background color, white text and some padding */
    footer {
      background-color: #555;
      color: white;
      padding: 15px;
    }

    /* On small screens, set height to 'auto' for sidenav and grid */
    @media screen and (max-width: 767px) {
      .sidenav {
        height: auto;
        padding: 15px;
      }
      .row.content {height:auto;}
    }
  </style>
    <script>
    function bvListHead(){
      var head = "<table id=\"table-recplist\" class=\"table table-hover\">";
      head+= "<thead>";
      head+= "<tr>";
      head+= "<th width=\"15%\">Instagram UID</th>";
      head+= "<th width=\"25%\">Username</th>";
      head+= "<th width=\"15%\">Status</th>";
      head+= "<th width=\"45%\">Post link</th>";
      head+= "</tr>";
      head+= "</thead>";
      return head;
    }

    function bvListFoot(){
      return "</table>";
    }

    $(document).ready(function(){
      $("#hashtag-input").on("input", function(){
        $('#main-list-panel').hide();
        $('#loading-list-panel').show();
        var hashtag = $(this).val();
        $.ajax({
          url: 'bvfetchjson.php?hashtag='+hashtag,
          success: function(result){
            var head = bvListHead();
            var foot = bvListFoot();
            var list = "";
            var decoded = JSON.parse(result);
            var userCount = decoded["userCount"];
            var postCount = decoded["postCount"];
            var userThatPosted = decoded["userThatPosted"];
            var monitorStatus = decoded["monitorStatus"];
            for (var x=0; x<monitorStatus.length; x++){
              list+="<tr>";
              list+="<td style=\"font-family:monospace;\">"+monitorStatus[x]['id']+"</td>";
              var username = monitorStatus[x]['username'];
              list+="<td><a href=\"https://www.instagram.com/"+username+"/\" target=\"_blank\">"+username+"</td>";
              if (monitorStatus[x]['posted']){
                var shortcode = monitorStatus[x]['shortcode'];
                list+="<td style=\"color:#00bb00\"><strong>SUDAH POSTING</strong></td>";
                list+="<td style=\"font-family:monospace;\"><a href=\"https://www.instagram.com/p/"+shortcode+"/\" target=\"_blank\">https://www.instagram.com/p/"+shortcode+"</td>";
              } else {
                list+="<td style=\"color:#bb0000\"><strong>BELUM POSTING</strong></td>";
                list+="<td style=\"font-family:monospace;\">-</td>";
              }
              list+="</tr>\n";
            }
            var info_str = "Ditemukan <strong>"+postCount+" postingan</strong>. Memonitor <strong>"+userCount+" pengguna</strong> dan <strong>"+userThatPosted+" telah diposting</strong> oleh pengguna yang dimonitor.";
            $("#panel-info").html(info_str);
            $('#main-list-panel').html(head+list+foot);
            $('#loading-list-panel').hide();
            $('#main-list-panel').show();
          }
        })
      });
    });
  </script>
</head>

<body>

    <nav class="navbar navbar-inverse">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <span class="navbar-brand">PaidPromote Monitoring App</span>
            </div>
            <div class="collapse navbar-collapse" id="myNavbar">
                <ul class="nav navbar-nav">
                    <!--<li><a href="input_bs.php">Input</a></li>
        <li class="active"><a href="#">List</a></li>-->
                </ul>
                <!--<ul class="nav navbar-nav navbar-right">
        <li><a href="#"><span class="glyphicon glyphicon-log-in"></span> Login</a></li>
      </ul>-->
            </div>
        </div>
    </nav>

    <div class="container-fluid text-center">
        <div class="row content">
            <!-- Konten utama -->
            <div class="col-sm-12 text-left">

                <div class="container-fluid text-center">
                    <div class="row content">
                        <!-- Konten utama -->
                        <div class="col-sm-12 text-left">
                            <h1>PP Monitor Search</h1>

                            <div id="panel-info" class="alert alert-info">Pilih hashtag dari pilihan berikut!</div>
                            <div class="panel panel-default">
                                <div class="panel-body">
                                  <select id="hashtag-input" type="text" class="form-control" name="hashtag">
                                    <?php
                                    $fh = fopen("bv_hashtags.txt", "r");
                                    if ($fh) {
                                      while (($line = fgets($fh)) !== false) {
                                        $line = trim($line);
                                        echo "<option value=\"$line\">#$line</option>\n";
                                      }
                                      fclose($fh);
                                    }
                                    ?>
                                  </select>
                                </div>
                            </div>
                            <div class="panel panel-default">
                                <div id="main-list-panel" class="panel-body" style="display:none;">
                                    <table id="table-recplist" class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th width="15%">Instagram UID</th>
                                                <th width="25%">Username</th>
                                                <th width="15%">Status</th>
                                                <th width="45%">Post link</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div id="loading-list-panel" class="panel-body text-center" style="display:none;">
                                  <h3>Loading...</h3>
                                  <img src="loadanim.svg" alt="Loading...">
                                  <br/>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="container-fluid text-center">
        <p>Copyright &copy; KOMINFO HMIF 2018</p>
    </footer>

</body>

</html>
