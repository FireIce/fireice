
function pluginCkeditor(data)
{
    var tmp = CKEDITOR.replace(data['name']);
    CKFinder.setupCKEditor( tmp, '/bundles/fireicesitetree/ckfinder' ) ;
}
