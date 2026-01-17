export interface UserProps {
    id: string;
    email: string;
    first_name?: string;
    last_name?: string;
    phone?: string;
    email_verified: boolean;
    roles: string[];
    permissions: string[];
    created_at: Date;
    updated_at: Date;
}

export class User {
    constructor(private props: UserProps) { }

    get id() { return this.props.id; }
    get email() { return this.props.email; }
    get first_name() { return this.props.first_name; }
    get last_name() { return this.props.last_name; }
    get phone() { return this.props.phone; }
    get email_verified() { return this.props.email_verified; }
    get roles() { return this.props.roles; }
    get permissions() { return this.props.permissions; }
    get created_at() { return this.props.created_at; }
    get updated_at() { return this.props.updated_at; }

    hasRole(role: string): boolean {
        return this.props.roles.includes(role);
    }

    hasPermission(permission: string): boolean {
        return this.props.permissions.includes(permission);
    }

    toJSON() {
        return {
            id: this.id,
            email: this.email,
            first_name: this.first_name,
            last_name: this.last_name,
            phone: this.phone,
            email_verified: this.email_verified,
            roles: this.roles,
            permissions: this.permissions,
            created_at: this.created_at,
            updated_at: this.updated_at
        };
    }
}
