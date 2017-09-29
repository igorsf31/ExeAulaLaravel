<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller as Controller;

use App\Models\Category;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class CategoriesController extends Controller {

	protected $rules = [
		'titulo' => 'required',
	];

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {

		//query
		$categories = Category::orderBy('created_at', 'DESC');

		//filters
		// titulo
		if ($nome = str_replace(" ", "%", Input::get("nome"))) {
			$categories = $categories->where('titulo', 'like', "%$nome%");
		}

		//status
		$status = Input::get('status');
		if ((isset($status)) and ($status != '')) {
			$categories = $categories->where('status', '=', $status);
		}

		//categories data for graphs (without paginate)
		$allCategories = $categories->get();

		//execute
		$categories = $categories->paginate(config('helpers.results_per_page'));

		return view("backend.newspapers.categories.index", [
				'categories' => $categories,
			]);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create() {
		return view('backend.newspapers.categories.form');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {

		$this->validate($request, $this->rules);

		$input = $request->all();

		$category = Category::create($input);

		$request->session()->flash('success', 'Categoria criada com sucesso!');
		return redirect()->route('newspapers.categories.index');

	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id) {
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id) {
		$category = Category::findOrFail($id);

		return view('backend.newspapers.categories.form', [
				'category' => $category
			]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id) {

		$category = Category::findOrFail($id);

		$input = $request->all();

		$category->fill($input)->save();

		$request->session()->flash('success', 'Categoria alterada com sucesso!');
		return redirect()->route('newspapers.categories.index');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Request $request, $id) {

		//when selected some entries to delete
		if ($request->selected) {

			$entries = explode(',', $request->selected);

			DB::transaction(function () use ($entries) {
					foreach ($entries as $entry) {
						$category = Category::findOrFail($entry);
						$category->update(array('status' => '0'));
						$category->delete();

						//log
						Log::create([
								'action'     => 'DELETE',
								'data_id'    => $category->id,
								'data_title' => $category->titulo
							]);

					}
				});
			//restore
			$restore = "<a href='".route('newspapers.categories.restore', 0)."?entries=".$request->selected."'>Desfazer</a>";
		}

		//when chosen to delete just one entry
		 else {
			$category = Category::findOrFail($id);
			$category->update(array('status' => '0'));

			DB::transaction(function () use ($category) {

					$category->delete();

					//log
					Log::create([
							'action'     => 'DELETE',
							'data_id'    => $category->id,
							'data_title' => $category->titulo
						]);

				});
			//restore
			$restore = "<a href='".route('newspapers.categories.restore', $id)."'>Desfazer</a>";
		}

		//return
		session()->flash('success', "Categoria(s) excluída(s) com sucesso. $restore");
		return redirect()->route('newspapers.categories.index');

	}

	/**
	 * Restore the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function restore($id) {
		//when restoring a lot of entries
		if ($entries = Input::get('entries')) {
			$entries = explode(',', $entries);

			DB::transaction(function () use ($entries) {
					foreach ($entries as $entry) {
						Category::withTrashed()->where('id', $entry)->restore();

						$category = Category::find($entry);

						//log
						Log::create([
								'action'     => 'RESTORE',
								'data_id'    => $category->id,
								'data_title' => $category->titulo
							]);
					}
				});
		}

		//when restoring 1 entry
		 else {

			DB::transaction(function () use ($id) {
					Category::withTrashed()->where('id', $id)->restore();

					$category = Category::find($id);

					//log
					Log::create([
							'action'     => 'RESTORE',
							'data_id'    => $category->id,
							'data_title' => $category->titulo
						]);

				});
		}

		session()->flash('success', 'Categoria(s) restaurada(s) com sucesso.');
		return redirect()->route('newspapers.categories.index');
	}
	/**
	 * Display a listing of soft deletes.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function trash() {
		$categories = Category::onlyTrashed()->paginate(config('helpers.results_per_page'));

		return view('backend.newspapers.categories.index', [
				'categories' => $categories,
				'trash'      => true,
			]);
	}

	/**
	 * Update the status of specified resource from storage.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function status() {
		$id     = (int) Input::get('id');
		$status = (int) Input::get('status');

		$code = 418;//I'm a teapot!

		if ($id and preg_match('/(0|1)/', $status)) {
			$category                     = Category::findOrFail($id);
			$category->status             = $status;
			if ($category->save()) {$code = 200;
			}

			//log
			Log::create([
					'action'     => 'STATUS',
					'data_id'    => $category->id,
					'data_title' => $category->titulo
				]);

		}

		return $code;
	}

	/**
	 * Change the order of categories at navbar
	 */
	public function order(Request $request) {
		$code = 418;//I'm a teapot!

		foreach ($request->item as $order => $id) {
			$category                     = Category::find($id);
			$category->order              = $order;
			if ($category->save()) {$code = 200;
			}
		}

		return $code;
	}

}
