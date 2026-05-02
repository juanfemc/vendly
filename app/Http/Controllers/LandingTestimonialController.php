<?php

namespace App\Http\Controllers;

use App\Models\LandingTestimonial;
use App\Services\AdminUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LandingTestimonialController extends Controller
{
    public function __construct(private AdminUpdateService $adminUpdateService)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', LandingTestimonial::class);

        if (! Schema::hasTable('landing_testimonials')) {
            return view('admin.testimonials.index', [
                'testimonials' => collect(),
                'needsMigration' => true,
            ]);
        }

        $testimonials = LandingTestimonial::orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.testimonials.index', [
            'testimonials' => $testimonials,
            'needsMigration' => false,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', LandingTestimonial::class);

        return view('admin.testimonials.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LandingTestimonial::class);

        if (! Schema::hasTable('landing_testimonials')) {
            return redirect('/admin/testimonials')->withErrors([
                'testimonials' => 'Primero ejecuta la migracion para crear la tabla de testimonios.',
            ]);
        }

        $testimonial = LandingTestimonial::create($this->validatedData($request, true));

        $this->adminUpdateService->record(
            'Testimonio creado',
            $testimonial->name,
            'testimonial',
            route('admin.testimonials.edit', $testimonial)
        );

        return redirect('/admin/testimonials')->with('success', 'Testimonio creado.');
    }

    public function edit(LandingTestimonial $testimonial): View
    {
        $this->authorize('update', $testimonial);

        return view('admin.testimonials.edit', compact('testimonial'));
    }

    public function update(Request $request, LandingTestimonial $testimonial): RedirectResponse
    {
        $this->authorize('update', $testimonial);

        $testimonial->update($this->validatedData($request));

        $this->adminUpdateService->record(
            'Testimonio actualizado',
            $testimonial->name,
            'testimonial',
            route('admin.testimonials.edit', $testimonial)
        );

        return redirect('/admin/testimonials')->with('success', 'Testimonio actualizado.');
    }

    public function toggle(LandingTestimonial $testimonial): RedirectResponse
    {
        $this->authorize('update', $testimonial);

        $testimonial->update(['is_active' => ! $testimonial->is_active]);

        $this->adminUpdateService->record(
            $testimonial->is_active ? 'Testimonio activado' : 'Testimonio desactivado',
            $testimonial->name,
            'testimonial',
            route('admin.testimonials.edit', $testimonial)
        );

        return redirect('/admin/testimonials')->with(
            'success',
            $testimonial->is_active ? 'Testimonio activado.' : 'Testimonio desactivado.'
        );
    }

    public function destroy(LandingTestimonial $testimonial): RedirectResponse
    {
        $this->authorize('delete', $testimonial);

        $name = $testimonial->name;
        $testimonial->delete();

        $this->adminUpdateService->record('Testimonio eliminado', $name, 'testimonial');

        return redirect('/admin/testimonials')->with('success', 'Testimonio eliminado.');
    }

    private function validatedData(Request $request, bool $creating = false): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'initials' => ['nullable', 'string', 'max:8'],
            'quote' => ['required', 'string', 'max:700'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $data['is_active'] = $request->boolean('is_active', $creating);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['initials'] = $data['initials'] ?: $this->initialsFromName($data['name']);

        return $data;
    }

    private function initialsFromName(string $name): string
    {
        return collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_substr($part, 0, 1))
            ->implode('');
    }
}
