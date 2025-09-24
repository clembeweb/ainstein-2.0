import Navigation from '@/components/Navigation'

export default function TenantLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <div className="flex">
      <Navigation />
      <main className="flex-1">
        {children}
      </main>
    </div>
  )
}