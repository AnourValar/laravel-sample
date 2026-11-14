<?php

namespace App\Handlers\Models\Ticket\Type;

interface RenderInterface
{
    /**
     * Рендеринг запроса тикета (карточка)
     *
     * @param \App\Ticket $ticket
     * @return string
     */
    public function renderRequest(\App\Ticket $ticket): string;

    /**
     * Рендеринг ответа тикета (форма)
     *
     * @param \App\Ticket $ticket
     * @param string $name
     * @return string
     */
    public function renderResponse(\App\Ticket $ticket, string $name): string;
}
