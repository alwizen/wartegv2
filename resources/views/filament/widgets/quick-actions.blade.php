<x-filament-widgets::widget>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($this->getActions() as $action)
            {{ $action->button()->extraAttributes([
                'class' => 'w-full text-lg px-8 py-6 rounded-xl shadow-md',
            ]) }}
        @endforeach
    </div>
</x-filament-widgets::widget>