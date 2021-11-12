<?php

final class DB
{
    protected static PDO $instance;
    protected static string $dbHost;
    protected static string $dbName;
    protected static string $dbUser;
    protected static string $dbPass;
    protected static string $dbPort;
    protected static string $dbCharset;

    public function __construct(
        string $dbHost,
        string $dbName,
        string $dbUser,
        string $dbPass,
        string $dbPort = "3306",
        string $dbCharset = "UTF-8"
    ) {
        self::$dbHost = $dbHost;
        self::$dbName = $dbName;
        self::$dbUser = $dbUser;
        self::$dbPass = $dbPass;
        self::$dbPort = $dbPort;
        self::$dbCharset = $dbCharset;
    }

    /**
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (empty(self::$instance)) {
            try {
                self::$instance = new PDO(
                    "mysql:host=" . self::$dbHost . ";port=" . self::$dbPort . ";dbname=" . self::$dbName,
                    self::$dbUser,
                    self::$dbPass
                );
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
                self::$instance->exec('SET NAMES utf8');
                self::$instance->exec('SET CHARACTER SET utf8');
            } catch (PDOException $error) {
                echo $error->getMessage();
            }
        }

        return self::$instance;
    }
    
}

final class UserInfo
{
    protected array $server;

    public function __construct()
    {
        $this->server = $_SERVER;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        $ip = $this->server['REMOTE_ADDR'];

        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            $ip = $this->server['HTTP_CLIENT_IP'];
        } elseif (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            $ip = $this->server['HTTP_X_FORWARDED_FOR'];
        }

        return $ip;
    }

    /**
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'];
    }

    /**
     * @return string
     */
    public function getPageUrl(): string
    {
        return $this->server['HTTP_REFERER'];
    }

}


final class RenderImage
{
    /**
     * @param string $url
     * @return bool|string
     */
    public function curlImage(string $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

}

final class Process
{

    public function run(): void
    {
        $userInfo = new UserInfo();
        $ipAddress = $userInfo->getIp();
        $userAgent = $userInfo->getUserAgent();
        $pageUrl = $userInfo->getPageUrl();

        $dbConnect = new DB("localhost", "siteinfo_db", "root", "root");
        $pdo = $dbConnect::getInstance();

        //$pageUrl = "http://infusemediatest.local/index3.html";

        $sql = "SELECT * 
                FROM visitors 
                WHERE ip_address IN (?)
                AND user_agent IN (?)
                AND page_url IN (?)
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ipAddress, $userAgent, $pageUrl]);
        $visitor = $stmt->fetch();

        $viewDate = date('Y-m-d H:i:s');
        if (!$visitor) {
            $sql = "INSERT INTO visitors (ip_address, user_agent, view_date, page_url, views_count) 
                    VALUES (?,?,?,?,?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$ipAddress, $userAgent, $viewDate, $pageUrl, 1]);
        } else {
            $viewsCount = $visitor['views_count'];
            $viewsCount++;
            $id = $visitor['id'];

            $sql = "UPDATE visitors 
                    SET view_date=?, views_count=? 
                    WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$viewDate, $viewsCount, $id]);
        }
    }

}

$process = new Process();
$process->run();

$url = $_SERVER['HTTP_HOST'] . "/picture.jpg";
$renderImage = new RenderImage();
echo $renderImage->curlImage($url);
