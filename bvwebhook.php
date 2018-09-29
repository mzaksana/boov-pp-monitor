<?php

/**
 * PaidPromote Hastag Monitoring (Codename Boov)
 * Copyright (C) 2018 Thiekus
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

  require_once "LINEBotTiny.php";
  require_once "bvconfig.php";
  require_once "bvfuncs.php";

  // Mendapatkan informasi profil (dumbed down from shania code)
  function get_profile_info($user_id,$force_fetch){
    global $channel_token;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Authorization: Bearer '.$channel_token
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, "https://api.line.me/v2/bot/profile/$user_id");
    $response = curl_exec($ch);
    $user_info = json_decode(str_replace('/','\/',$response),true); // Array asosiatif harus diaktifkan!
    return $user_info;
  }

  function get_nickname($user_id){
    $user_info = get_profile_info($user_id,false);
    $nama = explode(' ', $user_info['displayName']);
    $nama = ucfirst($nama[0]);
    return $nama;
  }

  function fetchIgHashtag($hashtag){
    $monlist = loadMonitorList("bv_monitorlist.txt");
    $posts = fetchHastagPostList($hashtag);
    $monstatus = compareMonitorStatus($monlist,$posts,$postedcnt);
    $data = array(
      'hashtag' => $hashtag,
      'userCount' => count($monlist),
      'postCount' => count($posts),
      'userThatPosted' => $postedcnt,
      'monitorStatus' => $monstatus
    );
    $data_encoded = json_encode($data);
    $data_hash = hash('sha1',$data_encoded);
    $data_path = dirname(__FILE__) . "/caches/$data_hash.json";
    if (!file_exists($data_path)){
      // Save ke file cache
      $fh = fopen($data_path, "w");
      fwrite($fh,$data_encoded);
      fclose($fh);
    }
    return $data_hash; // = fetchId
  }

  function getCachedMonData($fetchId){
    $data_path = dirname(__FILE__) . "/caches/$fetchId.json";
    if (file_exists($data_path)){
      // Save ke file cache
      $fh = fopen($data_path, "r");
      $data = fread($fh,filesize($data_path));
      fclose($fh);
      return json_decode($data);
    } else {
      return false;
    }
  }

  function bot_on_follow($client,$source){
    $nama = get_nickname($source['userId']);
    $pesan[] = array(
      'type' => 'sticker',
      'packageId' => '1',
      'stickerId' => '124'
    );
		$pesan[] = array(
      "Halo $nama!\nSelamat datang di bot PP Monitor helper untuk Infest! (Codename Boov)",
      "Untuk informasi lebih lanjut, silahkan ketik \"info\""
    );
		return $pesan;
  }

  function bot_on_message($client,$source,$msg){
    $type = strtolower($msg['type']);
    $id = $msg['id'];
    if ($type == "text"){
      $cmd = strtolower($msg['text']);
      if ($cmd[0] == "#"){
        // fetching new ig hashtag
        $param = explode("#",$cmd);
        $hashtag = trim($param[1]);
        $fetchId = fetchIgHashtag($hashtag);
        $monData = getCachedMonData($fetchId);
        if ($monData){
          $userCount = $monData->userCount;
          $postCount = $monData->postCount;
          $userThatPosted = $monData->userThatPosted;
          $pesan[] = array(
            'type' => 'template',
            'altText' => "Results for #$hashtag",
            'template' => array(
              'type' => 'buttons',
              //'title' => '#$hashtag',
              'text' => "Dari hashtag #$hashtag, ditemukan $postCount post, $userThatPosted dipost dari $userCount pengguna dimonitor",
              'actions' => array(
                array(
                  'type' => 'message',
                  'label' => 'List sudah post',
                  'text' => '!posted '.$fetchId
                ),
                array(
                  'type' => 'message',
                  'label' => 'List belum post',
                  'text' => '!nopost '.$fetchId
                ),
                array(
                  'type' => 'message',
                  'label' => 'Info',
                  'text' => 'info'
                )
              )
            )
          );
          //$pesan[] = "Dari hashtag #$hashtag, ditemukan $postCount post, $userThatPosted dipost dari $userCount pengguna dimonitor";
        } else {
          $pesan = "Gagal mendapatkan data untuk hashtag #$hashtag !";
        }
      } else
      if (substr($cmd,0,7) == "!posted"){
        // Listkan siapa yg sudah posting
        $param = explode(' ',$cmd);
        $monData = getCachedMonData(trim($param[1]));
        if ($monData){
          $hashtag = $monData->hashtag;
          foreach ($monData->monitorStatus as $user){
            if ($user->posted){
              $list[] = $user->username;
            }
          }
          if (isset($list)){
            $msg = "Berikut adalah pengguna dimonitor yang telah melakukan posting ke #$hashtag\n\n";
            $x=1;
            foreach ($list as $usr){
              $msg.="$x. $usr\n";
              $x++;
            }
          } else {
            $msg = "Tidak ditemukan pengguna termonitor yang mengepost #$hashtag !";
          }
          $pesan[] = $msg;
        } else {
          $pesan[] = "Tidak bisa mendapatkan data fetch!";
        }
      } else
      if (substr($cmd,0,7) == "!nopost"){
        // Listkan siapa yg belum posting
        $param = explode(' ',$cmd);
        $monData = getCachedMonData(trim($param[1]));
        if ($monData){
          $hashtag = $monData->hashtag;
          foreach ($monData->monitorStatus as $user){
            if (!$user->posted){
              $list[] = $user->username;
            }
          }
          if (isset($list)){
            $msg = "Berikut adalah pengguna dimonitor yang belum melakukan posting ke #$hashtag\n\n";
            $x=1;
            foreach ($list as $usr){
              $msg.="$x. $usr\n";
              $x++;
            }
          } else {
            $msg = "Tidak ditemukan pengguna termonitor yang tidak mengepost #$hashtag !";
          }
          $pesan[] = $msg;
        } else {
          $pesan[] = "Tidak bisa mendapatkan data fetch!";
        }
      } else
      if ($cmd == "about"){
        $pesan[] = array(
          'type' => 'template',
          'altText' => "(C) Bot codename Boov by Thiekus ~ September 2018",
          'template' => array(
            'type' => 'buttons',
            'title' => 'Tentang bot ini',
            'text' => '(C) Bot codename Boov by Thiekus ~ 2018',
            'actions' => array(
              array(
                'type' => 'uri',
                'label' => 'Kontak LINE',
                'uri' => 'http://line.me/ti/p/~ndezo'
              ),
              array(
                'type' => 'uri',
                'label' => 'My Website',
                'uri' => 'http://thiekus.com/'
              ),
              array(
                'type' => 'uri',
                'label' => 'My Instagram',
                'uri' => 'https://www.instagram.com/thiekus/'
              )
            )
          )
        );
      } else
      if ($cmd == "info"){
        $nama = get_nickname($source['userId']);
        $pesan = array(
          "Hai $nama! Disini kamu bisa membuat Twibbon bergerak secara mudah, hanya dengan mengirimkan gambar atau video yang ingin dijadikan twibbon.",
          "Tips & tricks:\n\n".
          "* Anda dapat mengedit foto yang akan dijadikan twibbon dengan menggunakan editor gambar dari LINE sebelum mengirimkannya\n".
          "* Proses pembuatan video baru akan dimulai ketika anda menekan tombol proses. Proses ini memerlukan berberapa detik atau menit dan pemrosesan video biasanya lebih lama.\n".
          "* Apabila anda tidak dapat mendownload video setelah selesai memproses, tekan tombol \"Link download\", lalu copy dan paste url download video yang dihasilkan ke browser handphone anda.\n".
          "* Video akan disesuaikan dengan durasi dari twibbon animasi yang tersedia. Apabila durasi video lebih pendek, program secara otomatis akan membuat efek \"boomerang\" pada sisa durasi yang tersisa.\n".
          "* Anda malas cari foto yang cocok? Gunakan foto profil LINE kamu dengan mengetik perintah !dp ",
          "Apabila ada masalah atau ingin kepo tentang bot ini? Ketik \"about\"."
        );
      } else {
        $pesan = "Ketik \"info\" untuk mendapatkan informasi tentang bot ini!";
      }
    }
    return $pesan;
  }

  // Jalankan client ketika webhook direquest!
  $client = new LINEBotTiny($channel_token, $channel_secret);
  foreach ($client->parseEvents() as $event) {
    $source = $event['source'];
    switch ($event['type']) {
      case 'follow': {
        $replies = bot_on_follow($client,$source);
        break;
      }
      case 'message': {
        $message = $event['message'];
        $replies = bot_on_message($client,$source,$message);
        break;
      }
      default: {
        error_log("Unsupporeted event type: " . $event['type']);
        break;
      }
    }

    if($replies){
      if (is_array($replies)){
        foreach ($replies as $reply){
          if (is_string($reply)){
            $msg[] = array(
              'type' => 'text',
              'text' => $reply
            );
          } else {
            $msg[] = $reply;
          }
        }
      } else {
        $msg = array(
          array(
            'type' => 'text',
            'text' => $replies
          )
        );
      }
      $client->replyMessage(array(
        'replyToken' => $event['replyToken'],
        'messages' => $msg
      ));
    }
  }

?>
