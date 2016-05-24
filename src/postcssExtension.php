<?php

namespace sahassar\postcss;

use Bolt\Extension\SimpleExtension;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\File\Stylesheet;
use Bolt\Controller\Zone;
use Bolt\Asset\Target;

class postcssExtension extends SimpleExtension
{
    private $CSSAssets = [
        'js/bolt-postcss.pkgd.js',
        'js/bolt-postcss.js',
        'js/beautify.js'
    ];

    private $JSAssets = [
        'js/bolt-uglifyjs.pkgd.js',
        'js/bolt-uglifyjs.js'
    ];

    protected function subscribe()
    {
        $app = $this->getContainer();
        $app->after(function(){
            $this->postcss_assets();
        }, 1000);
    }

    public function postcss_assets()
    {
        $app = $this->getContainer();
        $conf = array_merge(
            $this->getConfig(),
            [
                'editPath' => $app['resources']->getURL('bolt') . 'file/edit',
                'themePath' => '/themes/'.$app['config']->get('general/theme'),
                'currentPath' => $app['resources']->getURL('current')
            ]
        );

        $path = str_replace(
            $app['config']->get('general/theme'),
            '',
            $app['request']->get('file')
        );

        $conf['onCSSPage'] = ($conf['CSSsourceFile'] === $path);

        if ($app['request']->get('_route') === 'fileedit' &&
            $app['request']->get('namespace') === 'themes' &&
            $app['request']->get('file')) {

            $extension = pathinfo($app['request']->get('file'), PATHINFO_EXTENSION);
            $queue = [];

            if($extension === 'css'){
                $queue = $this->CSSAssets;
            }elseif($extension === 'js'){
                $queue = $this->JSAssets;
            }

            foreach($queue as $priority=>$assetFile){
                
                $asset = (new JavaScript($assetFile))
                        ->setZone(Zone::BACKEND)
                        ->setPriority($priority);

                $file = $this->getWebDirectory()->getFile($asset->getPath());
                $asset->setPackageName('extensions')->setPath($file->getPath());
        
                $app['asset.queue.file']->add($asset);
            }

            $script = '<script>var postCssConfig = '.json_encode($conf).';</script>';

            $asset = (new Snippet())
                  ->setCallback($script)
                  ->setZone(Zone::BACKEND)
                  ->setLocation(Target::END_OF_BODY);
            $app['asset.queue.snippet']->add($asset);
        }
    }
}
