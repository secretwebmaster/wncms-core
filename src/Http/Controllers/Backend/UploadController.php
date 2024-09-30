<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Website;
use Illuminate\Http\Request;

use Wncms\Models\Processor;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
// use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Wncms\Models\Video;


class UploadController extends Controller
{
    public function upload_image(Request $request)
    {
        // info($request->all());
        $website = Website::first();
        $media = $website->addMediaFromRequest('file')->toMediaCollection($request->collection ?? 'general');

        return response()->json(['location'=>$media->getFullUrl()]); 
    }

    public function upload_dropzone(Request $request)
    {
        // info($request->all());

        // create the file receiver
        $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));
    
        // check if the upload is success, throw exception or return response you need
        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException();
        }
    
        // receive the file
        $save = $receiver->receive();
    
        // check if the upload has finished (in chunk mode it will send smaller files)
        if ($save->isFinished()) {
            // save the file and return any response you need, current example uses `move` function. If you are
            // not using move, you need to manually delete the file by unlink($save->getFile()->getPathname())
            return $this->save_video_file($save->getFile(),$request->video_id);
        }
    
        // we are in chunk mode, lets send the current progress
        /** @var AbstractHandler $handler */
        $handler = $save->handler();
    
        return response()->json([
            "done" => $handler->getPercentageDone(),
        ]);
    }


    /**
     * Saves the file
     *
     * @param UploadedFile $file
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function save_video_file(UploadedFile $file,$video_id)
    { 
        // info("video id = " . $video_id);
        $date = date("Y/m/d");
        $fileName = md5($file.'video').'.'.$file->getClientOriginalExtension();
        $filePath = "mp4/{$date}/";
        $finalPath = storage_path("app/videos/{$filePath}");
        $video = Video::find($video_id);
        // $processor = Processor::where('status','active')->first();
        $video->transcoding_queues()->create([
            'path'=>$filePath,
            'format'=>$file->getClientOriginalExtension(),
            'filename' => $fileName,
        ]);

        // move the file name
        $file->move($finalPath, $fileName);

        return response()->json([
            'path' => $filePath,
            'name' => $fileName,
            // 'mime_type' => $mime
        ]);
    }
}
