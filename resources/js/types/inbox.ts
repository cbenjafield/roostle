export type InboxSummary = {
    id: number;
    name: string;
    catch_address: string;
    messages_count: number;
    unread_messages_count: number;
};

export type InboxDetails = {
    id: number;
    name: string;
    catch_address: string;
    smtp_host: string;
    smtp_port: number;
    smtp_username: string;
};

export type MessageSummary = {
    id: number;
    from_address: string;
    from_name: string | null;
    subject: string | null;
    received_at: string;
    is_read: boolean;
    attachments_count: number;
};

export type MessageParty = {
    address: string;
    name: string | null;
};

export type MessageDetails = {
    id: number;
    message_id: string | null;
    from_address: string;
    from_name: string | null;
    to: MessageParty[];
    cc: MessageParty[];
    subject: string | null;
    text_body: string | null;
    html_body: string | null;
    headers: { name: string; value: string }[];
    size: number;
    received_at: string;
    attachments: {
        id: number;
        filename: string;
        content_type: string;
        size: number;
    }[];
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
