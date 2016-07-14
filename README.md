# Zabbix + Reactio Integration

## 概要

Reactio の無料化によりその機能が解放され、様々な監視・アラートツールとの連携が可能になりました。  
これを機に Zabbix + Reactio 連携したのでまとめました。

[Reactioが無料になります](http://blog.reactio.jp/entry/2016/07/04/090000)

## 3行まとめ

・Zabbix DB alerts に Reactio インシデント ID カラムを登録し管理します。  
・Zabbix alertscript に Reactio API実行スクリプト配置。各種 config 設定します。  
・Zabbix Admin で Reactio API 実行スクリプト設定します。

## 環境

- Zabbix 3.0
- CentOS Linux release 7.2.1511 (Core)

Zabbix 3.0 がインストールされ起動されていることを前提とします。


## Zabbix 管理画面で Host 設定

<span style="color:#A9E2F3">※既に設定されている場合はスキップしてください。</style>

Configuration > Hosts > create  

- Host name: Project1

<img src="http://i.imgur.com/12vbmdE.png" width="400px"/>

## Reactio プロジェクト作成

プロジェクト毎にインシデントを管理します。

https://`<Organization ID>`.reactio.jp/settings/project

Zabbix 管理画面で設定している Host 名を Project 名とする。

![Imgur](http://i.imgur.com/f9YS11z.png)

## Reactio API 発行

プロジェクト作成ページと同ページ内にある API 項目の 「+」ボタンクリックし API KEY 発行します。

![Imgur](http://i.imgur.com/knN5mkT.png)

![Imgur](http://i.imgur.com/HvjKrvQ.png)

## zabbix-reactio インストール

```
$ cd /usr/lib/zabbix/alertscripts
$ git clone http://github.com/zabbix-reactio
```

## Zabbix DB情報 と Reactio で作成した Project と 発行した API KEY を設定ファイルに設定

```
$ cd /usr/lib/zabbix/alertscripts/zabbix-reactio
$ vi config.inc
```
- db_info に DB 情報設定
- `<Organization ID>` 設定
- Project = API KEY 設定

```
[db_info]
host = <DB Host>
user = <DB user>
pass = <DB pass>
db   = <DB name>

[reactio_url]
default = https://<Organization ID>.reactio.jp/api/v1/incidents

[api_key]
Project1 = <Project1's API KEY>
Project2 = <Project2's API KEY>
Project3 = <Project3's API KEY>
...
...
```

## DB カラム追加

- Zabbix alerts テーブルに Reactio Incident ID カラムを追加します。  

```
$ cd /usr/lib/zabbix/alertscripts/zabbix-reactio
$ mysql -h <DB Host> -u <DB user> -p<DB pass> <DB name> -e "`cat add_reactioincidentid.sql`"
$ mysql -h <DB Host> -u <DB user> -p<DB pass> <DB name> -e "SHOW COLUMNS FROM alerts"

+---------------------+---------------------+------+-----+---------+-------+
| Field               | Type                | Null | Key | Default | Extra |
+---------------------+---------------------+------+-----+---------+-------+
| alertid             | bigint(20) unsigned | NO   | PRI | NULL    |       |
| actionid            | bigint(20) unsigned | NO   | MUL | NULL    |       |
| eventid             | bigint(20) unsigned | NO   | MUL | NULL    |       |
| userid              | bigint(20) unsigned | YES  | MUL | NULL    |       |
| clock               | int(11)             | NO   | MUL | 0       |       |
| mediatypeid         | bigint(20) unsigned | YES  | MUL | NULL    |       |
| sendto              | varchar(100)        | NO   |     |         |       |
| subject             | varchar(255)        | NO   |     |         |       |
| message             | text                | NO   |     | NULL    |       |
| status              | int(11)             | NO   | MUL | 0       |       |
| retries             | int(11)             | NO   |     | 0       |       |
| error               | varchar(128)        | NO   |     |         |       |
| esc_step            | int(11)             | NO   |     | 0       |       |
| alerttype           | int(11)             | NO   |     | 0       |       |
| reactio_incident_id | int(11)             | NO   |     | 0       |       |    ← 追加されているのが確認できます
+---------------------+---------------------+------+-----+---------+-------+
```

>Reacito では全てのインシデントは ID で管理されています。  
>Zabbix で障害アラート通知時に Reactio インシデント作成API をコールし インシデントID を保存します。
>
>このインシデントIDは Zabbix で障害回復アラート通知時に Reatio インシデントステータス更新API をコールする際に利用します。

## Zabbix Media types: Reactio 作成

Administration > Media types `Create media type` ボタンクリック

<img src="http://i.imgur.com/iSOhzpP.png" width="400px" />

<img src="http://i.imgur.com/0OXYfbW.png" width="400px" />

以下値を入力し `Add` ボタンクリック

|*Item*|*Value*|
|---|---|
|Name|Reactio|
|Type|Script|
|Script name|zabbix-reactio/reactio.php|
|Script Parameters 1|{ALERT.SUBJECT}|
|Script Parameters 2|{ALERT.MESSAGE}|

## Zabbix Users: Reactio 作成

Administration > Users `Create media type` ボタンクリック

<img src="http://i.imgur.com/Bf5bqkc.png" width="400px" />

- Reactio ユーザ作成

<img src="http://i.imgur.com/n8aZiyV.png" width="400px" />

- Media タブをクリックし Media 情報入力

<img src="http://i.imgur.com/6yAyEe5.png" width="400px" />

<img src="http://i.imgur.com/VOKOpZI.png" width="400px" />

- Permission タブをクリックし Zabbix Super Admin 選択

<img src="http://i.imgur.com/cpZkPUT.png" width="200px" />

- Addボタン クリックし一覧に表示されることを確認

<img src="http://i.imgur.com/5ubxAKL.png" width="800px" />

## Zabbix Actions: Reactio Notification 作成

Configuration > Actions Create ボタンクリック

<img src="http://i.imgur.com/DDQcmrG.png" width="400px" />

- Action タブ選択し Action 情報入力

<img src="http://i.imgur.com/6hi0LPf.png" width="400px" />

|*Item*|*Value*|
|---|---|
|Name|Reactio Notification|
|Default subject|PROBLEM alert - {TRIGGER.NAME} is {TRIGGER.STATUS}|
|Default message|HOST: {HOST.NAME}<br/>TRIGGER_NAME: {TRIGGER.NAME}<br/>TRIGGER_STATUS: {TRIGGER.STATUS}<br/><b/>TRIGGER_SEVERITY: {TRIGGER.SEVERITY}<br/>DATETIME: {DATE} / {TIME}<br/>ITEM_ID: {ITEM.ID1}<br/>ITEM_NAME: {ITEM.NAME1}<br/>ITEM_KEY: {ITEM.KEY1}<br/>ITEM_VALUE: {ITEM.VALUE1}<br/>EVENT_ID: {EVENT.ID}<br/>TRIGGER_URL: {TRIGGER.URL}|
|Recovery message|チェック|
|Recovery subject|RECOVERY alert - {TRIGGER.NAME} is {TRIGGER.STATUS}|
|Recovery message|HOST: {HOST.NAME}<br/>TRIGGER_NAME: {TRIGGER.NAME}<br/>TRIGGER_STATUS: {TRIGGER.STATUS}<br/>TRIGGER_SEVERITY: {TRIGGER.SEVERITY}<br/>DATETIME: {DATE} / {TIME}<br/>ITEM_ID: {ITEM.ID1}<br/>ITEM_NAME: {ITEM.NAME1}<br/>ITEM_KEY: {ITEM.KEY1}<br/>ITEM_VALUE: {ITEM.VALUE1}<br/>EVENT_ID: {EVENT.ID}<br/>TRIGGER_URL: {TRIGGER.URL}|
|Enabled|チェック|

以下項目から判断して Reactio API を叩いてます。  

- subject の PROBLEM/RECOVERY
- HOST: {HOST.NAME}
- EVENT_ID: {EVENT.ID}

<span style="color:red">メッセージを整形する場合でも、 上記項目は残しておくようにしてください。</span>


- Operations タブ選択し Operations 情報入力

<img src="http://i.imgur.com/r7MXmVg.png" width="300px" />

<img src="http://i.imgur.com/Sx36MPZ.png" width="400px" />

<img src="http://i.imgur.com/bv5hcoM.png" width="400px" />

以上 Zabbix で Reactio 連携設定完了しました。


## 実行結果

- インシデント作成できた！  
- 作成したインシデントのステータスが更新された！

![Imgur](http://i.imgur.com/zWJpmAi.png)


## 総評

当方、運用し始めです。  
障害レベルによってメッセージを変更したりと今後更新検討致します。

是非以下も合わせて Zabbix-Slack 連携もご利用ください。

[zabbix3-slack](https://github.com/kenzo0107/zabbix3-slack)

以上  
ご清聴ありがとうございました。
