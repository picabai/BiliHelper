<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  Version: 0.0.2
 *  License: The MIT License
 *  Updated: 20180425 18:47:50
 */

namespace lkeme\BiliHelper;

use lkeme\BiliHelper\Curl;
use lkeme\BiliHelper\Sign;
use lkeme\BiliHelper\Log;
use lkeme\BiliHelper\User;

class Live
{
    public static $lock = 0;

    // RUN
    public static function run()
    {
        // TODO
        self::isSleep();
    }

    // GET RANDOW ROOM_ID
    public static function getUserRecommend()
    {
        while (1) {
            $page = rand(1, 10);
            $raw = Curl::get('https://api.live.bilibili.com/area/liveList?area=all&order=online&page=' . $page);
            $de_raw = json_decode($raw, true);
            if ($de_raw['code'] != '0') {
                continue;
            }
            break;
        }
        $rand_num = rand(1, 29);
        return $de_raw['data'][$rand_num]['roomid'];

    }

    // GET REALROOM_ID
    public static function getRealRoomID(int $room_id): int
    {
        $raw = Curl::get('https://api.live.bilibili.com/room/v1/Room/room_init?id=' . $room_id);
        $de_raw = json_decode($raw, true);
        if ($de_raw['code']) {
            Log::warning($room_id . ' : ' . $de_raw['msg']);
            return false;
        }
        if ($de_raw['data']['is_hidden']) {
            return false;
        }
        if ($de_raw['data']['is_locked']) {
            return false;
        }
        if ($de_raw['data']['encrypted']) {
            return false;
        }
        return $de_raw['data']['room_id'];

    }

    // Fishing Detection
    public static function fishingDetection($room_id): bool
    {
        //钓鱼检测
        if (!self::getRealRoomID($room_id)) {
            return false;
        }
        return true;
    }

    // RANDOM DELAY
    public static function randFloat($min = 3, $max = 5): bool
    {
        $rand = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        sleep($rand);
        return true;
    }

    //TO ROOM
    public static function goToRoom($room_id): bool
    {
        $payload = [
            'room_id' => $room_id,
        ];
        Curl::post('https://api.live.bilibili.com/room/v1/Room/room_entry_action', Sign::api($payload));
        Log::info('进入直播间[' . $room_id . ']抽奖!');
        return true;
    }

    // get Millisecond
    public static function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    // IS SLEEP
    public static function isSleep()
    {
        if (self::$lock > time()) {
            return;
        }
        self::$lock = time() + 5 * 60;

        $hour = date('H');
        if ($hour >= 2 && $hour < 6) {
            self::bannedVisit('sleep');
            Log::warning('休眠时间,暂停非必要任务,5小时后自动开启!');
            return;
        }

        $payload = [];
        $raw = Curl::get('https://api.live.bilibili.com/mobile/freeSilverAward', Sign::api($payload));
        $de_raw = json_decode($raw, true);
        if ($de_raw['msg'] == '访问被拒绝') {
            self::bannedVisit('ban');
            Log::warning('账号拒绝访问,暂停非必要任务,凌晨自动开启!');
        }
        return;
    }

    //被封禁访问
    public static function bannedVisit($arg)
    {
        // 获取当前时间
        $block_time = strtotime(date("Y-m-d H:i:s"));
        if ($arg == 'ban') {
            $unblock_time = strtotime(date("Y-m-d", strtotime("+1 day", $block_time)));
        } elseif ($arg == 'sleep') {
            $unblock_time = strtotime(date("Y-m-d", strtotime("+5 hours", $block_time)));
        } else {
            $unblock_time = time();
        }
        // +10 分钟
        $second = time() + ceil($unblock_time - $block_time) + 5 * 60;
        $hour = $second / 60 / 60;

        self::$lock = $second;
        \lkeme\BiliHelper\Silver::$lock = $second;
        \lkeme\BiliHelper\MaterialObject::$lock = $second;
        \lkeme\BiliHelper\Socket::$lock = $second;
        \lkeme\BiliHelper\GiftHeart::$lock = $second;

        return;
    }

}