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

declare module '@wordpress/element' {
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	export function createElement( type: any, props?: any, ...children: any[] ): unknown;
}

// declare module '@woocommerce/blocks-registry' {
// 	export function registerPaymentMethod( options: {
// 		name: string;
// 		label: unknown;
// 		content: unknown;
// 		editContent: unknown;
// 		edit: unknown;
// 		canMakePayment: () => boolean;
// 		ariaLabel: string;
// 		supports?: {
// 			features?: string[];
// 		};
// 	} ): void;
// }
