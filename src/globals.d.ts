declare module '@wordpress/data' {
	export function subscribe(
		listener: () => void,
		storeNameOrDescriptor?: string
	): () => void;

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	export function select< T = any >( storeNameOrDescriptor: string ): T;
}

declare module '@woocommerce/settings' {
	export function getSetting< T = Record< string, unknown > >(
		name: string,
		fallback?: T
	): T;
}
