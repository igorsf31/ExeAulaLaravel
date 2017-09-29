<?php

namespace App\Http\Controllers\Backend;


use App\Http\Controllers\Controller as Controller;

use App\Models\Newspaper;
use App\Http\Requests;
use App\Models\Category;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\ImageManagerStatic as Image;
use App\Models\GalleryNewspaper;

class NewspapersController extends Controller
{
    /**
     * upload_max_filesize = 10M
     * post_max_size = 20M
    */

    protected $rules = [
        'data' => 'required',
        'titulo'   => 'required',
        //'video_file' => 'mimes:mp4,x-flv,x-mpegURL,MP2T,3gpp,quicktime,x-msvideo,x-ms-wmv'
    ];


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        //query
        $newspapers = Newspaper::orderBy('newspapers.data', 'DESC')
            ->dateFromTo(Input::get('begin_date'), Input::get('end_date'));

        //filters
        // titulo
        if ($titulo = str_replace(" ", "%", Input::get("titulo")))
            $newspapers = $newspapers->where('newspapers.titulo', 'like', "%$titulo%");


        //status
        $status = Input::get('status');
        if ((isset($status)) and ($status != ''))
            $newspapers = $newspapers->where('newspapers.status', '=', $status);

        $category_id = Input::get('category_id');
        if ((isset($category_id)) and ($category_id != ''))
            $newspapers = $newspapers->where('newspapers.category_id', '=', $category_id);

        $newspapers = $newspapers->join('categories', 'categories.id', '=', 'newspapers.category_id');
        $newspapers = $newspapers->select('newspapers.*', 'categories.titulo as categoria');

        //newspapers data for graphs (without paginate)
        $allNewspapers = $newspapers->get();

        $categorias = Category::all();


        //execute
        $newspapers = $newspapers->paginate(config('helpers.results_per_page'));

        return view("backend.newspapers.index", [
            'newspapers' => $newspapers,
            'categorias' => $categorias
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::select('id', 'titulo')->pluck('titulo', 'id');
        return view('backend.newspapers.form', [
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $input = $request->all();
        $input['data'] = date('Y-m-d', strtotime(str_replace('/','-',$request->data)));

        $this->validate($request, $this->rules);

         //upload file
        if ($request->hasFile('imagem')) {

            $path = 'images/blog/';

            list($largura, $altura) = getimagesize($request->file('imagem'));

            //return dump($largura, $altura);

            if ($largura > $altura) {

                $largura_max = 600;
                $altura_max = 449;

            }
            if ($largura < $altura) {

                $largura_max = 451;
                $altura_max = 600;

            }
            if ($largura == $altura) {

                $largura_max = 600;
                $altura_max = 600;

            }


            //return dump($largura, $largura_max, $altura, $altura_max);

            $file = $request->file('imagem')->getClientOriginalName();
            $image_name = time() . "-" . $file;


            $img = imagecreatefromjpeg($request->file('imagem'));
            $original_x = imagesx($img); //largura
            $original_y = imagesy($img); //altura
            $diretorio = $path . "/" . $image_name;
            // verifica se a largura ou altura da imagem é maior que o valor
            // máximo permitido
            if (($original_x > $largura_max) || ($original_y > $altura_max)) {
                // verifica o que é maior na imagem, largura ou altura?
                if ($original_x > $original_y) {
                    $altura_max = ($largura_max * $original_y) / $original_x;
                } else {
                    $largura_max = ($altura_max * $original_x) / $original_y;
                }
                $nova = imagecreatetruecolor($largura_max, $altura_max);
                imagecopyresampled($nova, $img, 0, 0, 0, 0, $largura_max, $altura_max, $original_x, $original_y);
                imagejpeg($nova, $diretorio);
                imagedestroy($nova);
                imagedestroy($img);
                // se for menor, nenhuma alteração é feita
            } else {
                imagejpeg($img, $diretorio);
                imagedestroy($img);
            }


            //atribuindo o valor da variavel no campo da tabela para o insert
            $input['imagem'] = $image_name;

        $newspaper = Newspaper::create($input);


        $request->session()->flash('success', 'Postagem criada com sucesso!');
        return redirect()->route('newspapers.index');
    }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

        $newspaper = Newspaper::findOrFail($id);

        $newspaper->data = date('d/m/Y', strtotime($newspaper->data));
        $categories = Category::select('id', 'titulo')->pluck('titulo', 'id');

        return view('backend.newspapers.form', [
            'newspaper' => $newspaper,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        unset($this->rules['imagem']);
        $this->validate($request, $this->rules);

        $newspaper = Newspaper::findOrFail($id);

        $input = $request->all();

        if (empty($input['imagem']))
            unset($input['imagem']);

        $input['data'] = date('Y-m-d', strtotime(str_replace('/','-',$request->data)));

         //upload file
        if ($request->hasFile('imagem')) {

            //filename
            $filename = str_slug($request->titulo);
            $filename .= '-' . uniqid() . '.';
            $filename .= $request->file('imagem')->getClientOriginalExtension();

            $path = public_path() . "/images/blog/";

            if (!is_dir($path))
                mkdir($path, 0777, true);

            $img = Image::make($request->file('imagem'));

            $img->fit(870, 600, function ($constraint) {
                $constraint->upsize();
            });

            $img->save($path.$filename, 80);

            //data to save
            $input['imagem'] = $filename;
        }

        if(Input::hasFile('audio')){
            $file = Input::file('audio');
            $path = public_path() . "/audios/";

            #$filename = null;
            //filename
            $filename = str_slug($request->titulo);
            $filename .= '-' . uniqid() . '.';
            $filename .= $request->file('audio')->getClientOriginalExtension();

            if (!is_dir($path))
              @mkdir($path, 0777, true);

            $input['audio'] = $filename;

            $file->move($path, $filename);
        }

        $newspaper->fill($input)->save();


        $request->session()->flash('success', 'Postagem alterada com sucesso!');
        return redirect()->route('newspapers.index');
    }

   /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {

         //when selected some entries to delete
        if ($request->selected) {

            $entries = explode(',', $request->selected);

            DB::transaction(function() use($entries) {
                foreach ($entries as $entry) {
                    $newspaper = Newspaper::findOrFail($entry);
                    $newspaper->update(array('status' => '0'));
                    $newspaper->delete();
                }
            });
            //restore
            $restore = "<a href='".route('newspapers.restore', 0)."?entries=".$request->selected."'>Desfazer</a>";
        }

        //when chosen to delete just one entry
        else {
            $newspaper = Newspaper::findOrFail($id);
            $newspaper->update(array('status' => '0'));

            DB::transaction(function() use($newspaper) {

                $newspaper->delete();
            });
            //restore
            $restore = "<a href='".route('newspapers.restore', $id)."'>Desfazer</a>";
        }

        //return
        session()->flash('success', "Postagem(s) excluído(s) com sucesso. $restore");
        return redirect()->route('newspapers.index');

    }

    /**
     * Restore the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        //when restoring a lot of entries
        if ($entries = Input::get('entries')) {
            $entries = explode(',', $entries);

            DB::transaction(function() use($entries) {
                foreach ($entries as $entry) {
                    Newspaper::withTrashed()->where('id', $entry)->restore();

                    $newspaper = Newspaper::find($entry);
                }
            });
        }

        //when restoring 1 entry
        else {

            DB::transaction(function() use($id) {
                Newspaper::withTrashed()->where('id', $id)->restore();

                $newspaper = Newspaper::find($id);
            });
        }

        session()->flash('success', 'Postagem(s) restaurada(s) com sucesso.');
        return redirect()->route('newspapers.index');
    }
    /**
     * Display a listing of soft deletes.
     *
     * @return \Illuminate\Http\Response
     */
    public function trash()
    {
        $newspapers = Newspaper::onlyTrashed()->paginate(config('helpers.results_per_page'));

        return view('backend.newspapers.index', [
            'newspapers' => $newspapers,
            'trash' => true,
        ]);
    }


     /**
     * Update the status of specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function status() {
        $id = (int) Input::get('id');
        $status = (int) Input::get('status');

        $code = 418; //I'm a teapot!

        if ( $id and preg_match('/(0|1)/', $status) ) {
            $newspaper = Newspaper::findOrFail($id);
            $newspaper->status = $status;
            if ($newspaper->save()) $code = 200;

        }

        return $code;
    }

     /**
     * Change the order of newspapers at navbar
     */
    public function order(Request $request) {
        $code = 418; //I'm a teapot!

        foreach ($request->item as $order => $id) {
            $newspaper = Newspaper::find($id);
            $newspaper->order = $order;
            if ($newspaper->save()) $code = 200;
        }

        return $code;
    }

    public function order_news(Request $request) {
        $id = (int) Input::get('id');
        $ordem = (int) Input::get('ordem');

        $code = 418; //I'm a teapot!

        $newspaper = Newspaper::findOrFail($id);
        $newspaper->ordem = $ordem;
        if ($newspaper->save()) $code = 200;

        return $code;
    }



      /**
     * GalleryNewspaper Images
     */
    public function gallery(Request $request, $id) {

        $newspaper = Newspaper::findOrFail($id);
        $galleries = GalleryNewspaper::where('newspaper_id', $id)->get();

        return view("backend.newspapers.gallery", [
            'newspaper' => $newspaper,
            'galleries' => $galleries
        ]);
    }

    public function save(Request $request)
    {

        $newspaper = Newspaper::findOrFail($request->id);

        //filename
        $filename = str_slug($newspaper->nome);
        $filename .= '-' . uniqid() . '.';
        $filename .= $request->file('file')->getClientOriginalExtension();

        //destination folder
        $path = public_path() . "/images/blog/fotos/".$request->id.'/';

        if (!is_dir($path))
          @mkdir($path, 0777, true);

        $img = Image::make($request->file('file'));

        $img->fit(870, 600, function ($constraint) {
                $constraint->upsize();
            });

        $img->save($path.$filename, 80);

        //data to save
        $data['imagem'] = $filename;
        $data['newspaper_id'] = $request->id;

        return GalleryNewspaper::create($data);

    }

    public function remove(Request $request) {

    $image = GalleryNewspaper::findOrFail( $request->id );
    $path = public_path() . "/images/blog/fotos/".$request->gallery.'/'.$request->image;

    // clear image
    @unlink($path);
    $image->delete();

    return $request->id;

    }

    public function remove_files(Request $request) {

        $file = Newspaper::findOrFail($request->id);
        $path = public_path() . '/' . $request->folder . '/' .$request->file;

        // clear image
        unlink($path);
        if($request->folder == 'videos')
            $file->update(array('video_file' => ''));
        else
            $file->update(array('audio' => ''));

        return $request->id;
    }

    public function legenda(Request $request) {

    $legenda = GalleryNewspaper::findOrFail( $request->id );
    $legenda->update(array('legenda' => $request->legenda));

    return $request->id;

    }

}
