@extends('layouts.lodepart-fancy')

@section('title')
<title>Populating document JSON collection</title>
@stop

@section('adaptable-area')
<div id="page-content">
    <?php

    use App\Eloquent\Document;

$subCollectionPath = env('FORMEX.DOCUMENTS.JSON.PATH');
    $collectionPath = public_path($subCollectionPath);
    $comnatList = array();

    $counter = 0;
    foreach (glob($collectionPath . '/*/*.json') as $file) {

        $pos = strpos($file, $subCollectionPath);
        if ($pos > 0) {
            $filename = substr($file, $pos + strlen($subCollectionPath) + 1);
        } else {
            $filename = basename($file);
        }

        $content = file_get_contents($file);

        $metadata = json_decode($content);
//        if (!isset($metadata->num)) {
//            $metadata->num = 'INFOHUB';
//        }
        $title = $metadata->title . '<br/>' . $metadata->subject;
        $subject = $metadata->subject;
        // check if num does not start with a zero '0'
        if (strlen($metadata->num) < 4) {
            $metadata->num = '0' . $metadata->num;
        }

        $actID = 'comnat:COM_' . $metadata->year . '_' . $metadata->num . '_FIN';
        $eli_lang_code = $metadata->eli_lang_code;
        $splitFilename = explode("/", $filename);
        $fileNameWithoutExt = $splitFilename[1];
        $year = $metadata->year;
        $num = $metadata->num;

        $actURI = Document::addDocument($title, $subject, $actID, $eli_lang_code, env('SITE_NAME'), $filename, $year, $num);
        if ($actURI != null) {
            $counter++;
            if (empty($comnatList[$actID])) {
                $comnatList[$actID] = true;
                $genericAct = substr($actURI, 0, strlen($actURI) - 4);
                Document::add_label_topic($actID, $genericAct);
                Document::add_label_procedureType($actID);
            }
        }
    }
    echo $counter . ' documents have been added!';
    ?>
</div>
@stop