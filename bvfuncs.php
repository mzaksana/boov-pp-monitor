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

  function fetchData($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $response = curl_exec($ch);
    return $response;
  }

  function fetchHastagPostList($hashtag){
    $hash_base_url = "https://www.instagram.com/explore/tags/$hashtag/";
    // fetch data satu2
    $hash_fetch_url = $hash_base_url . "?__a=1";
    while (1){
      $fetched_data = fetchData($hash_fetch_url);
      $resp_data = json_decode($fetched_data);
      foreach ($resp_data->graphql->hashtag->edge_hashtag_to_media->edges as $node){
        $media_posts[] = $node->node;
      }
      if ($resp_data->graphql->hashtag->edge_hashtag_to_media->page_info->has_next_page){
        $hash_fetch_url = $hash_base_url . "?__a=1&max_id=".$resp_data->graphql->hashtag->edge_hashtag_to_media->page_info->end_cursor;
      } else {
        break;
      }
    }
    return $media_posts;
  }

  function loadMonitorList($monlistfile){
    $fh = fopen($monlistfile, "r");
    if ($fh) {
      while (($line = fgets($fh)) !== false) {
        $spl = explode(":",trim($line));
        $user_node[] = array(
          'username' => $spl[0],
          'id' => $spl[1]
        );
      }
      fclose($fh);
      return $user_node;
    } else {
      return NULL;
    }
  }

  function compareMonitorStatus($monlist,$posts,&$postedcnt){
    // Asumsikan semua user belum mengepost
    $postedcnt = 0;
    foreach ($monlist as $user){
      $user['posted'] = false;
      $user['shortcode'] = "";
      $monresult[] = $user;
    }
    // Iterasikan semua posts yg masuk
    foreach ($posts as $post){
      $id = $post->owner->id;
      $x = 0;
      foreach ($monresult as $user){
        if ($user['id'] == $id){
          $shortcode = $post->shortcode;
          $monresult[$x]['posted'] = true;
          $monresult[$x]['shortcode'] = $shortcode;
          $postedcnt++;
          break;
        }
        $x++;
      }
    }
    return $monresult;
  }

?>
