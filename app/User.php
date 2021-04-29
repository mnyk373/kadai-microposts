<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    /**
     * このユーザが所有する投稿。（ Micropostモデルとの関係を定義）
     */
    public function microposts()
    {
        return $this->hasMany(Micropost::class);
    }
    
    /**
     * このユーザがフォロー中のユーザ。（ Userモデルとの関係を定義）
     */
    public function followings()
    {
        return $this->belongsToMany(User::class, 'user_follow', 'user_id', 'follow_id')->withTimestamps();
    }

    /**
     * このユーザをフォロー中のユーザ。（ Userモデルとの関係を定義）
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_follow', 'follow_id', 'user_id')->withTimestamps();
    }

    /**
     * このユーザに関係するモデルの件数をロードする。
     */
    public function loadRelationshipCounts()
    {
        $this->loadCount(['microposts', 'followings', 'followers', 'favorites']);
    }
    
    /**
     * $userIdで指定されたユーザをフォローする。
     *
     * @param  int  $userId
     * @return bool
     */
    public function follow($userId)
    {
        // すでにフォローしているかの確認
        $exist = $this->is_following($userId);
        // 対象が自分自身かどうかの確認
        $its_me = $this->id == $userId;

        if ($exist || $its_me) {
            // すでにフォローしていれば何もしない
            return false;
        } else {
            // 未フォローであればフォローする
            $this->followings()->attach($userId);
            return true;
        }
    }

    /**
     * $userIdで指定されたユーザをアンフォローする。
     *
     * @param  int  $userId
     * @return bool
     */
    public function unfollow($userId)
    {
        // すでにフォローしているかの確認
        $exist = $this->is_following($userId);
        // 対象が自分自身かどうかの確認
        $its_me = $this->id == $userId;

        if ($exist && !$its_me) {
            // すでにフォローしていればフォローを外す
            $this->followings()->detach($userId);
            return true;
        } else {
            // 未フォローであれば何もしない
            return false;
        }
    }

    /**
     * 指定された $userIdのユーザをこのユーザがフォロー中であるか調べる。フォロー中ならtrueを返す。
     *
     * @param  int  $userId
     * @return bool
     */
    public function is_following($userId)
    {
        // フォロー中ユーザの中に $userIdのものが存在するか
        return $this->followings()->where('follow_id', $userId)->exists();
    }
    
    /**
     * このユーザとフォロー中ユーザの投稿に絞り込む。
     */
    public function feed_microposts()
    {
        // このユーザがフォロー中のユーザのidを取得して配列にする
        $userIds = $this->followings()->pluck('users.id')->toArray();
        // このユーザのidもその配列に追加
        $userIds[] = $this->id;
        // それらのユーザが所有する投稿に絞り込む
        return Micropost::whereIn('user_id', $userIds);
    }
    



    /**
     * このユーザが追加したお気に入りの一覧（Micropostモデルとの関係を定義）
     * memo:belongsToMany(Modelクラス,中間テーブル,中間テーブルカラム（自身）,中間テーブルカラム（関係先）
     */
    public function favorites()
    {
        return $this->belongsToMany(Micropost::class, 'favorites', 'user_id', 'micropost_id')->withTimestamps();
    }
    
    
    /**
     * 指定された $micropostsIdをこのユーザがお気に入り登録しているか調べる。登録しているならtrueを返す。
     *
     * @param  int  $micropostsId
     * @return bool
     */
    public function is_favorite($micropostsId)
    {
        // フォロー中ユーザの中に $userIdのものが存在するか
        return $this->favorites()->where('micropost_id', $micropostsId)->exists();
    }
    
    /**
     * $micropostsIdで指定された投稿をお気に入りに追加する。
     *
     * @param  int  $micropostsId
     * @return bool
     */
    public function favorite($micropostsId)
    {
        // すでにお気に入り追加しているかの確認
        $exist = $this->is_favorite($micropostsId);

        if ($exist) {
            // すでにお気に入り追加していれば何もしない
            return false;
        } else {
            // お気に入りに追加されてなければ追加する
            $this->favorites()->attach($micropostsId);
            return true;
        }
    }
    
    /**
     * $micropostsIdで指定された投稿をお気に入りから削除する。
     *
     * @param  int  $micropostsId
     * @return bool
     */
    public function unfavorite($micropostsId)
    {
        // すでにお気に入り追加しているかの確認
        $exist = $this->is_favorite($micropostsId);

        if ($exist) {
            // すでにお気に入り追加していれば、お気に入りから削除
            $this->favorites()->detach($micropostsId);
            return true;
        } else {
            // お気に入りに追加されてなければ何もしない
            return false;
        }
    }
}
