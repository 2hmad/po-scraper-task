import Link from "next/link";

export default function Home() {
  return (
    <div className="flex flex-col items-center justify-center h-screen">
      <Link href="/products" className="text-blue-600 underline text-2xl">
        Go to Products Page
      </Link>
    </div>
  );
}
