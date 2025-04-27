<x-filament-widgets::widget>
        <div class="flex flex-wrap gap-4">
            @foreach ($this->getActions() as $action)
                {{ $action->button()->extraAttributes([
                   'class' => 'w-full sm:w-48 text-lg px-8 py-6 rounded-xl shadow-md',
                ]) }}
            @endforeach
        </div>
</x-filament-widgets::widget>
