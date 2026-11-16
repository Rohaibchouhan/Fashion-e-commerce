export interface Address {
  id: string;
  firstName: string;
  lastName: string;
  street: string;
  apartment?: string;
  city: string;
  state: string;
  zipCode: string;
  country: string;
  phone: string;
  isDefaultShipping?: boolean;
  isDefaultBilling?: boolean;
}

export interface UserRoleInfo {
  id: number;
  name: string;
  slug: string;
  description?: string;
}

export interface User {
  id: string;
  name: string;
  firstName?: string;
  lastName?: string;
  email: string;
  phone?: string;
  avatar?: string;
  status?: "active" | "inactive" | "pending_activation" | "suspended";
  role?: string;
  roleSlug?: string;
  roles?: string[];
  createdAt: string;
  emailVerifiedAt?: string | null;
  ordersCount?: number;
  ordersTotal?: number;
  addresses: Address[];
}

export interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  token?: string;
}
