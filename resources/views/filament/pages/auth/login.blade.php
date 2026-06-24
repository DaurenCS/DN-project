<x-filament-panels::page.simple>
    @php $isAdmin = request()->is('admin/*'); @endphp

    <div style="
    display: flex;
    background: rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 12px;
    padding: 3px;
    margin-bottom: 20px;
    gap: 3px;
  ">
        <a href="/admin/login" style="
      flex: 1;
      padding: 7px 0;
      text-align: center;
      font-size: 12px;
      font-weight: 500;
      border-radius: 9px;
      text-decoration: none;
      transition: background 0.18s, color 0.18s;
      {{ $isAdmin
        ? 'background: white; color: #111; box-shadow: 0 1px 4px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.08);'
        : 'background: transparent; color: var(--color-text-secondary); border: 1px solid transparent;'
      }}
    ">Администратор</a>

        <a href="/curator/login" style="
      flex: 1;
      padding: 7px 0;
      text-align: center;
      font-size: 12px;
      font-weight: 500;
      border-radius: 9px;
      text-decoration: none;
      transition: background 0.18s, color 0.18s;
      {{ !$isAdmin
        ? 'background: white; color: #111; box-shadow: 0 1px 4px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.08);'
        : 'background: transparent; color: var(--color-text-secondary); border: 1px solid transparent;'
      }}
    ">Куратор</a>
    </div>

    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}
        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>
</x-filament-panels::page.simple>
