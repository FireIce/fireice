<?php

namespace fireice\Backend\Plugins\Uploadimage\Model;

class BackendModel extends \fireice\Backend\Plugins\BasicPlugin\Model\BackendModel
{

    public function getData($sitetree_id, $module_id, $language, $moduleEntyty, $module_type, $rows=false)
    {
        $query = $this->em->createQuery("
            SELECT 
                ".(($module_type === \fireice\Backend\Modules\Model\BackendModel::TYPE_LIST) ? 'md.row_id,' : '')."
                md.plugin_type, 
                md.plugin_name,
                plg.id_data AS plugin_id_data,
                plg.alt AS plugin_value_alt,
                plg.src AS plugin_value_src,
                md.status
            FROM 
                ".$moduleEntyty." md, 
                FireicePlugins".ucfirst($this->controller->getValue('type'))."Bundle:plugin".$this->controller->getValue('type')." plg,
                DialogsBundle:moduleslink m_l,
                DialogsBundle:modulespluginslink mp_l
            WHERE (md.final = 'Y' OR md.final = 'W')
            AND md.eid IS NULL
            ".(($rows !== false) ? 'AND md.row_id IN ('.implode(',', $rows).')' : '')."
            AND m_l.up_tree = :up_tree
            AND m_l.up_module = :up_module
            AND m_l.id = mp_l.up_link
            AND mp_l.up_plugin = md.idd

            AND md.plugin_id = plg.id_group
            AND md.plugin_type = :plugin_type");
        
        $query->setParameters(array(
            'up_tree' => $sitetree_id,
            'up_module' => $module_id,
            'plugin_type' => $this->controller->getValue('type')
        ));         

        $result = $query->getScalarResult();

        $return = array ();
        $plugins = array ();

        foreach ($result as $val) {
            if (!isset($plugins[$val['plugin_name']])) $plugins[$val['plugin_name']] = array ();

            if ($module_type === \fireice\Backend\Modules\Model\BackendModel::TYPE_LIST) {
                $plugins[$val['plugin_name']][$val['row_id']][$val['plugin_id_data']] = $val;
            } else {
                $plugins[$val['plugin_name']][$val['plugin_id_data']] = $val;
            }
        }

        if ($module_type === \fireice\Backend\Modules\Model\BackendModel::TYPE_LIST) {

            foreach ($plugins as $key => $value) {
                if (!isset($return[$key])) $return[$key] = array ();

                foreach ($value as $k => $v) {

                    $return[$key][$k] = array (
                        'row_id' => $v[0]['row_id'],
                        'plugin_type' => $v[0]['plugin_type'],
                        'plugin_name' => $v[0]['plugin_name'],
                        'show_add_button' => 1,
                        'plugin_value' => array ()
                    );

                    foreach ($v as $k2 => $v2) {
                        $return[$key][$k]['plugin_value'][$v2['plugin_id_data']] = array (
                            'alt' => $v2['plugin_value_alt'],
                            'src' => $v2['plugin_value_src']
                        );
                    }
                }

                $return[$key] = array_values($return[$key]);
            }
        } else {
            foreach ($plugins as $key => $value) {
                $return[$key] = array (
                    'plugin_type' => $value[0]['plugin_type'],
                    'plugin_name' => $value[0]['plugin_name'],
                    'show_add_button' => 1,
                    'plugin_value' => array ()
                );

                foreach ($value as $k => $v) {
                    $return[$key]['plugin_value'][$v['plugin_id_data']] = array (
                        'alt' => $v['plugin_value_alt'],
                        'src' => $v['plugin_value_src']
                    );
                }
            }
        }

        if ($module_type === \fireice\Backend\Modules\Model\BackendModel::TYPE_LIST) {
            $tmp = array ();
            foreach ($return as $key => $value) {
                foreach ($value as $v2) {
                    $tmp[] = $v2;
                }
            }
            return $tmp;
        }

        return array_values($return);
    }

    public function setData($data)
    {
        $plugin_entity_class = 'fireice\\Backend\\Plugins\\'.ucfirst($this->controller->getValue('type')).'\\Entity\\plugin'.$this->controller->getValue('type');

        $id_group = null;
        $settings = $this->controller->getSettings();
        if (false !== $settings) {
            if (!isset($settings['resize']['x'])) $settings['resize']['x'] = '*';
            if (!isset($settings['resize']['y'])) $settings['resize']['y'] = '*';
        }

        foreach ($data as $k => $v) {
            if ($id_group == null) {
                $plugin_entity = new $plugin_entity_class();
                $plugin_entity->setIdGroup(0);
                $plugin_entity->setIdData($k);
                if (false !== $settings && isset($settings['resize'])) {

                    $tmp = $this->resize($v['src'], array ('x' => $settings['resize']['x'], 'y' => $settings['resize']['y']));
                    $v['src'] = $tmp['src'];
                }
                $plugin_entity->setValue($v);

                $this->em->persist($plugin_entity);
                $this->em->flush();

                $id_group = $plugin_entity->getId();

                $plugin_entity->setIdGroup($id_group);

                $this->em->persist($plugin_entity);
                $this->em->flush();

                continue;
            }

            $plugin_entity = new $plugin_entity_class();
            $plugin_entity->setIdGroup($id_group);
            $plugin_entity->setIdData($k);
            if (false !== $settings && isset($settings['resize'])) {

                $tmp = $this->resize($v['src'], array ('x' => $settings['resize']['x'], 'y' => $settings['resize']['y']));
                $v['src'] = $tmp['src'];
            }
            $plugin_entity->setValue($v);

            $this->em->persist($plugin_entity);
            $this->em->flush();
        }

        return $id_group;
    }

    // ------------------------------------------------------------------

    protected $image;
    protected $type;
    protected $filename;

    protected function resize($image, $size)
    {
        if (null === $this->setImage($image)) {
            return null;
        }

        $a = $this->getResizeSize($size['x'], $size['y']);

        if (true === $a['need']) {
            $oImage = ImageCreateTrueColor($a['new']['x'], $a['new']['y']);

            imagecopyresampled($oImage, $this->getImage(), 0, 0, 0, 0, $a['new']['x'], $a['new']['y'], $a['old']['x'], $a['old']['y']);

            switch ($this->type) {
                case 'image/png':
                    $s = 'imagepng';
                    break;
                case 'image/gif':
                    $s = 'imagegif';
                    break;
                default:
                    $s = 'imagejpeg';
                    break;
            }

            $dirname = (($size['x'] !== '*') ? $size['x'] : '').'x'.(($size['y'] !== '*') ? $size['y'] : '');

            if (!is_dir($this->getImagesDir().'/'.$dirname)) {
                mkdir($this->getImagesDir().'/'.$dirname);
            }

            $s($oImage, $this->getImagesDir().'/'.$dirname.'/'.$this->filename);

            imagedestroy($oImage);

            return array (
                'src' => $this->getImagesUrlPart().'/'.$dirname.'/'.$this->filename,
                'size' => array (
                    'x' => $a['new']['x'],
                    'y' => $a['new']['y']
                )
            );
        }

        $info = getimagesize($this->getSrcRealPath($image));

        return array (
            'src' => $image,
            'size' => array (
                'x' => $info[0],
                'y' => $info[1]
            )
        );
    }

    protected function setImage($file)
    {
        if (null !== $this->image) {
            imagedestroy($this->image);
            $this->image = null;
            $this->type = null;
            $this->filename = null;
        }

        $type = getimagesize($this->getSrcRealPath($file));

        switch ($type["mime"]) {
            case 'image/jpeg':
                $function = 'imagecreatefromjpeg';
                break;
            case 'image/png':
                $function = 'imagecreatefrompng';
                break;
            case 'image/gif':
                $function = 'imagecreatefromgif';
                break;
            default:
                return null;
        }

        $this->type = $type["mime"];
        $this->filename = basename($this->getSrcRealPath($file));
        $this->image = $function($this->getSrcRealPath($file));

        return true;
    }

    protected function getResizeSize($x, $y)
    {
        $a = array (
            'old' => array (
                'x' => $this->getImageSX(),
                'y' => $this->getImageSY(),
            ),
            'new' => array (
                'x' => $x,
                'y' => $y,
            ),
        );

        /**
         * Выясняем коэффициент сжатия
         *
         */
        $m = null;

        /**
         * По оси X
         *
         */
        if ('*' !== $x && false === empty($x) && $a['old']['x'] > $x) {
            $m = 100 * $x / $a['old']['x'];
        }

        /**
         * По оси Y
         *
         */
        if ('*' !== $y && false === empty($y) && $a['old']['y'] > $y) {
            $mm = 100 * $y / $a['old']['y'];

            if (null === $m || $mm < $m) {
                $m = $mm;
            }
        }

        /**
         * Высчитываем новые размеры
         *
         */
        if (null !== $m) {
            $a['new']['x'] = ceil($a['old']['x'] / 100 * $m);
            $a['new']['y'] = ceil($a['old']['y'] / 100 * $m);
        }

        $a['need'] = null !== $m;

        return $a;
    }

    protected function getImage()
    {
        return $this->image;
    }

    protected function getImageSX()
    {
        return imageSX($this->getImage());
    }

    protected function getImageSY()
    {
        return imageSY($this->getImage());
    }

    // Возвращает полный путь до файла, заданного через: /uploads/images/name.jpg
    protected function getSrcRealPath($filename)
    {
        return $this->container->getParameter('project_web_directory').$filename;
    }
    
    // Возвращает путь до директории images
    protected function getImagesDir()
    {
        return rtrim($this->container->getParameter('upload_images_directory'), '/\\');
    }
    
    protected function getImagesUrlPart()
    {
        return $this->container->getParameter('images_url_part');
    }
}