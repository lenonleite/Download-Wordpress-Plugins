<?php
/**
 * Created by PhpStorm.
 * User: lenon
 * Date: 27/10/16
 * Time: 23:29
 */

namespace Aszone\DownloadPlugins;

use GuzzleHttp\ClientInterface;


class DownloadPlugins
{
    const URL_WORDPRESS_TAG = "https://wordpress.org/plugins/tags/";

    private $type;

    private $numberOfPages;

    private $client;

    private $verbose;

    private $path;

    public function __construct($type,$client,$verbose=false,$numberOfPages=1)
    {
        $this->type = $type;
        $this->client = $client;
        $this->numberOfPages = $numberOfPages;
        $this->verbose = $verbose;
    }

    public function get()
    {
        $listOfPlugins = $this->getListOfPagesPlugins();

        foreach($listOfPlugins as $pluginPage)
        {
            $linkZip = $this->getLinkOfDownload($pluginPage);

            if($this->verbose)
            {
                echo $linkZip."\n";
            }

            $zipFiles[] = $linkZip;
        }

        return $zipFiles;
    }

    public function downloadToFolders($path="/../resources/")
    {
        $zip = new \ZipArchive;
        $zipsFiles = $this->downloadZips($path);

        foreach($zipsFiles as $zipFile)
        {

            if ($zip->open($zipFile) === TRUE) {
                $zip->extractTo(__DIR__.$path);
                $zip->close();

                if($this->verbose)
                {
                    echo "Save Folder: ".$zipFile." - Path : ".__DIR__.$path."\n";
                }

            }

        }

    }

    public function downloadZips($setPath="/../resources/")
    {
        $linksZip = $this->get();

        foreach($linksZip as $link)
        {
            $body = $this->client->get($link)->getBody()->getContents();
            $name = $this->getNameOfLink($link);
            $path = __DIR__.$setPath;
            file_put_contents($path.$name,$body);
            $results[] = $path.$name;
        }

        return $results;
    }

    private function getNameOfLink($link)
    {
        preg_match("/plugin\/(.*?)$/",$link,$matches);
        if(!empty($matches[1])){
            return $matches[1];
        }
        return false;
    }

    private function getListOfPagesPlugins()
    {
        $urlDefault = DownloadPlugins::URL_WORDPRESS_TAG.$this->type;
        $results = [];

        for($i=0;$i<$this->numberOfPages;$i++){

            $url=$urlDefault;
            if($i!=0){
                $url = $urlDefault."/page/".($i+1);
            }
            $body = $this->client->get($url)->getBody()->getContents();
            $tempResults = $this->getLinksInBody($body);

            if(empty($tempResults)){
                $i = $this->numberOfPages;
            }

            $results = array_merge($tempResults,$results);
        }

        return $results;
    }

    private function getLinksInBody($body="")
    {
        preg_match_all("/<h4><a href=\"(.*?)\">(.*?)<\/h4>/", $body, $matches);
        if(!empty($matches[1])){
            return $matches[1];
        }
        return [];
    }

    private function getLinkOfDownload($url)
    {
        $body = $this->client->get($url)->getBody()->getContents();
        preg_match("/itemprop=\'downloadUrl\' href=\'(.*?)\'>/", $body, $matches);
        if(!empty($matches[1])){
            return $matches[1];
        }
        return false;
    }

}