<?php
namespace florbalMohelnice\Models;
/**
 * Description of Files model
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class FilesModel extends BaseModel {
    
    public function getFluent() {
        throw new \Nette\NotImplementedException();
    }
    
    public function getArticlesIdirContent() {
        
        $files = array_reverse(scandir(ARTICLES_IDIR));
        array_pop($files);
        array_pop($files);
        $files = @array_combine($files, $files);
        return $files == FALSE ? array():$files;
    }
}

