import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'

// Semplifichiamo: permettiamo tutto e gestiamo l'auth nelle pagine
export function middleware(request: NextRequest) {
  return NextResponse.next()
}

export const config = {
  matcher: [
    '/((?!_next/static|_next/image|favicon.ico).*)',
  ],
}