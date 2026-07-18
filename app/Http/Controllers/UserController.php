<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UserFilterRequest;
use App\Models\Enquiry;
use App\Models\GisEnquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
   
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(UserFilterRequest $request)
    {
        $validated = $request->validated();
        $query = User::query()->with(['primaryRole', 'createdBy']);

        $query
            ->when($validated['q'] ?? null, function (Builder $query, string $keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->when($validated['role'] ?? null, function (Builder $query, string $role) {
                $query->whereHas('primaryRole', fn (Builder $query) => $query->where('name', $role));
            })
            ->when($validated['status'] ?? null, function (Builder $query, string $status) {
                $query->where('is_active', $status === 'active');
            });

        $sort = $validated['sort'] ?? '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        return view('administrator.users.index', [
            'users' => $query->orderBy($column, $direction)->paginate(25)->appends($validated),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $validated,
            'summary' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('is_active', true)->count(),
                'inactive' => User::query()->where('is_active', false)->count(),
                'roles' => Role::query()->count(),
            ],
        ]);

    }
    public function loginpage(){
        return view('administrator.login');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort_unless(request()->user()->hasCrmPermission('user.create'), 403);

        return view('administrator.users.AddUser', [
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'created_by' => $request->user()->id,
                'invited_at' => now(),
                'activated_at' => ($validated['is_active'] ?? false) ? now() : null,
            ]);

            $user->syncPrimaryRole($validated['role']);
        });

        return redirect()
            ->route('users.index')
            ->with('status', 'User created successfully.');
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

        public function dashboard(){
       $hasGisEnquiries = Schema::hasTable('gis_enquiries');

       return view('dashboard', [
           'summary' => [
               'users' => User::query()->count(),
               'active_users' => User::query()->where('is_active', true)->count(),
               'enquiries' => Enquiry::query()->where('spam_status', 'clean')->count(),
               'gis_enquiries' => $hasGisEnquiries
                   ? GisEnquiry::query()->where('spam_status', 'clean')->count()
                   : 0,
               'suspected_spam' => Enquiry::query()->where('spam_status', 'suspected')->count()
                   + ($hasGisEnquiries ? GisEnquiry::query()->where('spam_status', 'suspected')->count() : 0),
           ],
       ]);
    }

}
