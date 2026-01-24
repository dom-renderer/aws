<?php

namespace App\Http\Controllers;

use App\Http\Controllers\VariableProductController;
use App\Http\Controllers\BundledProductController;
use App\Http\Controllers\SimpleProductController;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\AwProduct;

class ProductController extends Controller
{
    protected $title = 'Products';
    protected $view = 'products.';

    public function __construct()
    {
        $this->middleware('permission:products.index')->only(['index']);
        $this->middleware('permission:product-management')->only(['steps']);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->ajax();
        }

        $title = $this->title;
        $subTitle = 'Manage products here';
        return view($this->view . 'index', compact('title', 'subTitle'));
    }

    public function ajax()
    {
        $query = AwProduct::query();

        return datatables()
        ->eloquent($query)
        ->addColumn('action', function ($row) {
            $html = '';
            if (auth()?->user()?->isAdmin() || auth()->guard('web')->user()->can('products.edit')) {
                $html .= '<a href="' . route('product-management', ['type' => encrypt($row->type), 'step' => encrypt(1), 'id' => encrypt($row->id)]) . '" class="btn btn-sm btn-primary"> <i class="fa fa-edit"> </i> </a>&nbsp;';
            }

            return $html;
        })
        ->editColumn('product_type', function ($row) {
            if ($row->product_type == 'simple') {
                return "Simple";
            } else if ($row->product_type == 'variable') {
                return "Variable";
            } else if ($row->product_type == 'bundle') {
                return "Bundle";
            } else {
                return "Unknown";
            }
        })
        ->rawColumns(['action', 'product_type'])
        ->addIndexColumn()
        ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function steps(Request $request, $type = null, $step = null, $id = null)
    {
        $notFoundMessage = 'You are lost';

        if (empty($type) || empty($step) || !Helper::isValidEncryption($type) || !Helper::isValidEncryption($step)) {
            abort(404, $notFoundMessage);
        }

        $type = decrypt($type);

        if (!in_array($type, ['simple', 'variable', 'bundle'])) {
            abort(404, $notFoundMessage);
        }

        $step = decrypt($step);

        if ($type == 'simple' && !($step >= 1 && $step <= 8)) {
            abort(404, $notFoundMessage);
        }

        if ($type == 'variable' && !($step >= 1 && $step <= 9)) {
            abort(404, $notFoundMessage);
        }

        if ($type == 'bundle' && !($step >= 1 && $step <= 4)) {
            abort(404, $notFoundMessage);
        }

        if (empty($id)) {
            $product = AwProduct::create([
                'name' => 'Untitled Product',
                'slug' => uniqid() . '-' . uniqid(),
                'product_type' => $type
            ]);

            return redirect()->route('product-management', ['type' => encrypt($type), 'step' => encrypt($step), 'id' => encrypt($product->id)]);
        }

        $id = decrypt($id);
        $product = AwProduct::find($id);
        $product->product_type = $type;
        $product->save();

        if ($request->method() == 'GET') {
            if ($type == 'simple') {
                return SimpleProductController::view($product, $step, $type);
            }

            if ($type == 'variable') {
                return VariableProductController::view($product, $step, $type);
            }

            if ($type == 'bundle') {
                return BundledProductController::view($product, $step, $type);
            }
        } else {
            if ($type == 'simple') {
                return SimpleProductController::store($request, $step, $id, $type);
            }

            if ($type == 'variable') {
                return VariableProductController::store($request, $step, $id);
            }

            if ($type == 'bundle') {
                return BundledProductController::store($request, $step, $id);
            }
        }
    }
}
