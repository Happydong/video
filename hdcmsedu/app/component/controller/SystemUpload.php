<?php namespace app\component\controller;

use houdunwang\request\Request;
use houdunwang\file\File;
use houdunwang\config\Config;
use system\model\Attachment;
use Db;

/**
 * 系统上传处理
 * Class SystemUpload
 *
 * @package app\component\controller
 */
class SystemUpload extends Common
{
    public function __construct()
    {
        $this->auth();
    }

    /**
     * 上传文件
     *
     * @param \system\model\Attachment $attachment
     *
     * @return array
     * @throws \Exception
     */
    public function uploader(Attachment $attachment)
    {
        Config::set('upload', array_merge(Config::get('upload'), v('config.site.upload')));
        Config::set('upload.mold', 'local');
        Config::set('upload.path', Config::get('upload.path').'/'.date('Y/m/d'));
        Config::set('upload.size', v('config.site.upload.size') * 1024);
        //前台自定义模式
        $path = Request::post('uploadDir', Config::get('upload.path'));
        $file = File::path()->path($path)->upload();
        if ($file) {
            $data = [
                'uid'        => v('user.info.uid'),
                'siteid'     => 0,
                'name'       => $file[0]['name'],
                'module'     => '',
                'filename'   => $file[0]['filename'],
                'path'       => $file[0]['path'],
                'extension'  => strtolower($file[0]['ext']),
                'createtime' => time(),
                'size'       => $file[0]['size'],
                'status'     => 1,
                'data'       => Request::post('data', ''),
                'content'    => Request::post('content', ''),
                'user_type'  => 'user',
            ];
            $attachment->save($data);

            return ['valid' => 1, 'message' => $file[0]['path']];
        } else {
            return ['valid' => 0, 'message' => File::getError()];
        }
    }

    /**
     * 获取文件列表
     *
     * @return array
     */
    public function filesLists()
    {
        $db   = Db::table('attachment')
                  ->where('uid', v('user.info.uid'))
                  ->whereIn('extension', explode(',', strtolower(Request::post('extensions'))))
                  ->where('user_type', 'user')
                  ->where('module', '')
                  ->orderBy('id', 'DESC');
        $Res  = $db->paginate(32);
        $data = [];
        if ($Res->toArray()) {
            foreach ($Res as $k => $v) {
                $data[$k]['createtime'] = date('Y/m/d', $v['createtime']);
                $data[$k]['size']       = \Tool::getSize($v['size']);
                $data[$k]['url']        = preg_match('/^http/i', $v['path']) ? $v['path']
                    : __ROOT__.'/'.$v['path'];
                $data[$k]['path']       = $v['path'];
                $data[$k]['name']       = $v['name'];
            }
        }

        return ['data' => $data, 'page' => $Res->links()->show()];
    }

    /**
     * 获取本地文件列表
     *
     * @return array
     */
    public function filesListsLocal()
    {
        $db   = Db::table('attachment')
                  ->where('uid', v('user.info.uid'))
                  ->whereIn(
                      'extension',
                      explode(',', strtolower(Request::post('extensions')))
                  )
                  ->where('user_type', 'user')
                  ->where('path', "like", "attachment%")
                  ->orderBy('id', 'DESC');
        $Res  = $db->paginate(32);
        $data = [];
        if ($Res->toArray()) {
            foreach ($Res as $k => $v) {
                $data[$k]['createtime'] = date('Y/m/d', $v['createtime']);
                $data[$k]['size']       = \Tool::getSize($v['size']);
                $data[$k]['url']        = preg_match('/^http/i', $v['path']) ? $v['path']
                    : __ROOT__.'/'.$v['path'];
                $data[$k]['path']       = $v['path'];
                $data[$k]['name']       = $v['name'];
            }
        }

        return ['data' => $data, 'page' => $Res->links()->show()];
    }

    /**
     * 删除图片
     *
     * @return array
     */
    public function removeImage()
    {
        $db   = Db::table('attachment');
        $file = $db->where('id', $_POST['id'])->where('uid', v('user.info.uid'))->first();
        if (is_file($file['path'])) {
            unlink($file['path']);
        }
        $db->where('id', $_POST['id'])->where('uid', v('user.info.uid'))->delete();

        return $this->success('删除成功');
    }
}